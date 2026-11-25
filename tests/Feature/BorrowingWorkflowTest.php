<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPeminjaman;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian reservasi, persetujuan, pengembalian, stok, dan denda. */
class BorrowingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan peminjam dapat meminta banyak alat tanpa mengurangi stok saat pengajuan. */
    public function test_borrower_can_request_multiple_tools_without_reserving_stock(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop, $proyektor] = $this->buatAlatDenganStok([3, 2]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($peminjam)->post(route('peminjam.peminjaman.store'), [
            'tanggal_pinjam' => today()->toDateString(),
            'tanggal_kembali_rencana' => today()->addDays(2)->toDateString(),
            'alat' => [
                $laptop->id => 2,
                $proyektor->id => 1,
            ],
        ]);

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertRedirect(route('peminjam.peminjaman'));
        $this->assertDatabaseHas('detail_peminjaman', [
            'alat_id' => $laptop->id,
            'jumlah' => 2,
        ]);
        $this->assertDatabaseHas('detail_peminjaman', [
            'alat_id' => $proyektor->id,
            'jumlah' => 1,
        ]);
        $this->assertSame(3, $laptop->fresh()->stok);
        $this->assertSame(2, $proyektor->fresh()->stok);
    }

    /** Memastikan petugas memeriksa seluruh stok sebelum mengurangi stok mana pun. */
    public function test_staff_approval_checks_all_stock_before_decrementing_any(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $petugas = User::factory()->create(['role' => 'petugas']);
        [$laptop, $proyektor] = $this->buatAlatDenganStok([3, 2]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($peminjam)->post(route('peminjam.peminjaman.store'), [
            'tanggal_pinjam' => today()->toDateString(),
            'tanggal_kembali_rencana' => today()->addDays(2)->toDateString(),
            'alat' => [
                $laptop->id => 2,
                $proyektor->id => 1,
            ],
        ])->assertRedirect(route('peminjam.peminjaman'));

        $peminjaman = Peminjaman::firstOrFail();
        $proyektor->update(['stok' => 0]);

        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.approve', $peminjaman))
            ->assertSessionHasErrors('status');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertSame('Menunggu Persetujuan', $peminjaman->fresh()->status);
        $this->assertSame(3, $laptop->fresh()->stok);
        $this->assertSame(0, $proyektor->fresh()->stok);

        $proyektor->update(['stok' => 2]);
        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->post(route('petugas.peminjaman.approve', $peminjaman))
            ->assertRedirect();

        $this->assertSame('Dipinjam', $peminjaman->fresh()->status);
        $this->assertSame(1, $laptop->fresh()->stok);
        $this->assertSame(1, $proyektor->fresh()->stok);
        $this->assertSame($petugas->id, $peminjaman->fresh()->petugas_id);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->post(route('petugas.peminjaman.approve', $peminjaman))
            ->assertSessionHasErrors('status');
        $this->assertSame(1, $laptop->fresh()->stok);
        $this->assertSame(1, $proyektor->fresh()->stok);
    }

    /** Memastikan persetujuan ditolak sebelum tanggal mulai peminjaman. */
    public function test_staff_cannot_approve_before_the_borrow_start_date(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $petugas = User::factory()->create(['role' => 'petugas']);
        [$laptop] = $this->buatAlatDenganStok([2]);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => today()->addDay()->toDateString(),
            'tanggal_kembali_rencana' => today()->addDays(2)->toDateString(),
            'status' => 'Menunggu Persetujuan',
        ]);
        DetailPeminjaman::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_id' => $laptop->id,
            'jumlah' => 1,
        ]);

        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.approve', $peminjaman))
            ->assertSessionHasErrors('status');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertSame('Menunggu Persetujuan', $peminjaman->fresh()->status);
        $this->assertSame(2, $laptop->fresh()->stok);
    }

    /** Memastikan pengembalian terlambat menambah denda harian per peminjaman. */
    public function test_late_return_automatically_adds_one_daily_fine_per_borrowing(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop, $kamera] = $this->buatAlatDenganStok([0, 0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop, $kamera], today()->subDay()->toDateString());
        $details = $peminjaman->detailPeminjaman->keyBy('alat_id');

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'detail' => [
                $details[$laptop->id]->id => ['kondisi_kembali' => 'Baik'],
                $details[$kamera->id]->id => ['kondisi_kembali' => 'Baik'],
            ],
        ])->assertRedirect(route('petugas.pengembalian.index'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'status' => 'Terlambat',
            'denda' => 1000,
            'denda_kerusakan' => 0,
        ]);
        $this->assertSame('Dikembalikan', $peminjaman->fresh()->status);
        $this->assertSame(1, $laptop->fresh()->stok);
        $this->assertSame(1, $kamera->fresh()->stok);
    }

    /** Memastikan denda dihitung per hari kalender penuh setelah jatuh tempo. */
    public function test_late_fine_counts_each_full_calendar_day_after_the_due_date(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop], today()->subDays(7)->toDateString());

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'detail' => [
                $peminjaman->detailPeminjaman->first()->id => ['kondisi_kembali' => 'Baik'],
            ],
        ])->assertRedirect(route('petugas.pengembalian.index'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'denda' => 7000,
            'denda_kerusakan' => 0,
        ]);
    }

    /** Memastikan peminjaman aktif ditandai terlambat saat daftar dibuka. */
    public function test_active_borrowing_is_marked_late_when_the_lists_are_opened(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => today()->subDays(3)->toDateString(),
            'tanggal_kembali_rencana' => today()->subDay()->toDateString(),
            'status' => 'Dipinjam',
        ]);

        $this->actingAs($peminjam)
            ->get(route('peminjam.peminjaman'))
            ->assertOk()
            ->assertSee('Terlambat');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertSame('Terlambat', $peminjaman->fresh()->status);
    }

    /** Memastikan daftar petugas menandai peminjaman yang melewati jatuh tempo. */
    public function test_staff_borrowing_list_automatically_marks_overdue_loans(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => today()->subDays(3)->toDateString(),
            'tanggal_kembali_rencana' => today()->subDay()->toDateString(),
            'status' => 'Dipinjam',
        ]);

        $this->actingAs($petugas)
            ->get(route('petugas.peminjaman.index'))
            ->assertOk()
            ->assertSee('Terlambat');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertSame('Terlambat', $peminjaman->fresh()->status);
    }

    /** Memastikan penghapusan pengembalian mengembalikan stok tersedia yang bertambah. */
    public function test_deleting_a_return_record_reverses_only_the_returned_available_stock(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $admin = User::factory()->create(['role' => 'admin']);
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop], today()->addDay()->toDateString());
        $detail = $peminjaman->detailPeminjaman->first();

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'detail' => [
                $detail->id => ['kondisi_kembali' => 'Baik'],
            ],
        ])->assertRedirect(route('petugas.pengembalian.index'));

        $pengembalianId = $peminjaman->fresh()->pengembalian->id;
        $this->actingAs($admin)
            ->delete(route('admin.pengembalian.destroy', $pengembalianId))
            ->assertRedirect(route('admin.pengembalian.index'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseMissing('pengembalian', ['id' => $pengembalianId]);
        $this->assertSame('Dipinjam', $peminjaman->fresh()->status);
        $this->assertNull($peminjaman->fresh()->pengembalian_diajukan_at);
        $this->assertNull($detail->fresh()->kondisi_kembali);
        $this->assertSame(0, $laptop->fresh()->stok);
    }

    /** Memastikan denda kerusakan dicatat dan alat baik kembali ke stok tersedia. */
    public function test_damage_fine_is_recorded_and_only_good_tools_return_to_available_stock(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop, $kamera] = $this->buatAlatDenganStok([0, 0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop, $kamera], today()->subDays(2)->toDateString());
        $details = $peminjaman->detailPeminjaman->keyBy('alat_id');

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'denda_kerusakan' => 25000,
            'catatan' => 'Kamera rusak saat digunakan.',
            'detail' => [
                $details[$laptop->id]->id => ['kondisi_kembali' => 'Baik'],
                $details[$kamera->id]->id => ['kondisi_kembali' => 'Rusak Berat'],
            ],
        ])->assertRedirect(route('petugas.pengembalian.index'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'status' => 'Rusak',
            'denda' => 27000,
            'denda_kerusakan' => 25000,
        ]);
        $this->assertSame(1, $laptop->fresh()->stok);
        $this->assertSame(0, $kamera->fresh()->stok);
        $this->assertSame('Rusak Berat', $details[$kamera->id]->fresh()->kondisi_kembali);
    }

    public function test_return_cannot_be_processed_twice_or_increase_stock_twice(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$alat] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$alat], today()->addDay()->toDateString());
        $detail = $peminjaman->detailPeminjaman->first();
        $data = [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'detail' => [$detail->id => ['kondisi_kembali' => 'Baik']],
        ];

        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.store'), $data)
            ->assertRedirect(route('petugas.pengembalian.index'));
        $this->post(route('petugas.pengembalian.store'), $data)
            ->assertSessionHasErrors('peminjaman_id');

        $this->assertSame(1, $alat->fresh()->stok);
        $this->assertSame(1, Pengembalian::where('peminjaman_id', $peminjaman->id)->count());
        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'petugas_id' => $petugas->id,
        ]);
    }

    public function test_lost_tool_does_not_increase_available_stock(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$alat] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$alat], today()->addDay()->toDateString());
        $detail = $peminjaman->detailPeminjaman->first();

        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'denda_kerusakan' => 0,
            'detail' => [$detail->id => ['kondisi_kembali' => 'Hilang']],
        ])->assertRedirect(route('petugas.pengembalian.index'));

        $this->assertSame(0, $alat->fresh()->stok);
        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'status' => 'Hilang',
        ]);
    }

    /** Memastikan alat kondisi baik tidak dikenai denda. */
    public function test_fine_cannot_be_charged_for_tools_returned_in_good_condition(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop], today()->addDay()->toDateString());

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'denda_kerusakan' => 10000,
            'detail' => [
                $peminjaman->detailPeminjaman->first()->id => ['kondisi_kembali' => 'Baik'],
            ],
        ])->assertSessionHasErrors('denda_kerusakan');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseMissing('pengembalian', ['peminjaman_id' => $peminjaman->id]);
        $this->assertSame('Dipinjam', $peminjaman->fresh()->status);
        $this->assertSame(0, $laptop->fresh()->stok);
    }

    /** Memastikan denda kerusakan disertai keterangan kerusakan. */
    public function test_damage_fine_requires_an_explanation(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        [$laptop] = $this->buatAlatDenganStok([0]);
        $peminjaman = $this->buatPeminjaman($peminjam, [$laptop], today()->addDay()->toDateString());
        $detail = $peminjaman->detailPeminjaman->first();

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($petugas)->post(route('petugas.pengembalian.store'), [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'denda_kerusakan' => 10000,
            'detail' => [
                $detail->id => ['kondisi_kembali' => 'Rusak Ringan'],
            ],
        ])->assertSessionHasErrors('catatan');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseMissing('pengembalian', ['peminjaman_id' => $peminjaman->id]);
        $this->assertSame('Dipinjam', $peminjaman->fresh()->status);
    }

    /** Memastikan perubahan tanggal kembali menghitung ulang denda keterlambatan. */
    public function test_updating_return_date_recalculates_automatic_late_fine(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $admin = User::factory()->create(['role' => 'admin']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => today()->subDays(5)->toDateString(),
            'tanggal_kembali_rencana' => today()->subDays(3)->toDateString(),
            'status' => 'Dikembalikan',
        ]);
        $pengembalian = Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => today()->toDateString(),
            'status' => 'Terlambat',
            'denda' => 3000,
            'denda_kerusakan' => 0,
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->actingAs($admin)->put(route('admin.pengembalian.update', $pengembalian), [
            'tanggal_kembali' => today()->subDay()->toDateString(),
            'denda_kerusakan' => 0,
        ])->assertRedirect(route('admin.pengembalian.index'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertDatabaseHas('pengembalian', [
            'id' => $pengembalian->id,
            'status' => 'Terlambat',
            'denda' => 2000,
            'denda_kerusakan' => 0,
        ]);
    }

    /** Menyiapkan data pendukung pengujian agar setiap kasus memiliki alat atau peminjaman yang konsisten. */
    private function buatAlatDenganStok(array $stok): array
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $kategori = Kategori::create(['nama_kategori' => 'Peralatan']);
        $alats = [];

        foreach ($stok as $index => $jumlah) {
            $alats[] = Alat::create([
                'nama_alat' => 'Alat '.($index + 1),
                'kategori_id' => $kategori->id,
                'kode_alat' => 'ALT-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'stok' => $jumlah,
            ]);
        }

        return $alats;
    }

    /** Menyiapkan data pendukung pengujian agar setiap kasus memiliki alat atau peminjaman yang konsisten. */
    private function buatPeminjaman(User $peminjam, array $alats, string $tanggalKembali): Peminjaman
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => today()->subDays(2)->toDateString(),
            'tanggal_kembali_rencana' => $tanggalKembali,
            'pengembalian_diajukan_at' => now(),
            'status' => 'Dipinjam',
        ]);

        foreach ($alats as $alat) {
            DetailPeminjaman::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_id' => $alat->id,
                'jumlah' => 1,
                'kondisi_pinjam' => 'Baik',
            ]);
        }

        return $peminjaman->load('detailPeminjaman');
    }
}
