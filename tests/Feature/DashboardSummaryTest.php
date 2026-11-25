<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian ringkasan tugas yang ditampilkan pada dasbor. */
class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan dasbor administrator menampilkan pekerjaan yang perlu ditindaklanjuti. */
    public function test_admin_dashboard_shows_work_that_needs_attention(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan sistem')
            ->assertSee('Menunggu persetujuan petugas')
            ->assertSee('Pengembalian menunggu petugas')
            ->assertSee('Tidak ada permohonan yang menunggu.')
            ->assertSee('Tidak ada pengembalian yang menunggu pemeriksaan.');
    }

    /** Memastikan dasbor petugas memfokuskan persetujuan dan pengembalian. */
    public function test_staff_dashboard_focuses_on_approval_and_return_tasks(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)
            ->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Tugas hari ini')
            ->assertSee('Permohonan baru')
            ->assertSee('Barang untuk diperiksa')
            ->assertDontSee('Peminjaman Terbaru')
            ->assertDontSee('Quick Actions');
    }

    /** Memastikan transaksi selesai tetap tampak pada ringkasan meski tugas aktif kosong. */
    public function test_staff_dashboard_shows_totals_for_completed_records(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);
        $alat = Alat::create([
            'nama_alat' => 'Laptop',
            'kategori_id' => $kategori->id,
            'kode_alat' => 'LAP-001',
            'stok' => 1,
            'kondisi' => 'Baik',
        ]);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'petugas_id' => $petugas->id,
            'tanggal_pinjam' => '2026-11-20',
            'tanggal_kembali_rencana' => '2026-11-21',
            'status' => 'Dikembalikan',
        ]);
        $peminjaman->detailPeminjaman()->create([
            'alat_id' => $alat->id,
            'jumlah' => 1,
            'kondisi_pinjam' => 'Baik',
            'kondisi_kembali' => 'Baik',
        ]);
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'petugas_id' => $petugas->id,
            'tanggal_dikembalikan' => '2026-11-21',
            'status' => 'Tepat Waktu',
            'denda' => 0,
            'denda_kerusakan' => 0,
        ]);

        $this->actingAs($petugas)
            ->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSeeText('Ringkasan data tersimpan')
            ->assertSeeText('Jenis alat')
            ->assertSeeText('Total peminjaman')
            ->assertSeeText('Total pengembalian')
            ->assertSeeText('Angka tugas di bawah hanya menghitung transaksi yang masih menunggu tindakan atau belum selesai.');
    }

    /** Memastikan dasbor peminjam menampilkan langkah berikutnya. */
    public function test_borrower_dashboard_shows_a_clear_next_step(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        $this->actingAs($peminjam)
            ->get(route('peminjam.dashboard'))
            ->assertOk()
            ->assertSee('Lihat status alat dan langkah yang perlu Anda lakukan.')
            ->assertSee('Ajukan Peminjaman')
            ->assertSee('Belum ada peminjaman')
            ->assertSee('Buka katalog alat');
    }
}
