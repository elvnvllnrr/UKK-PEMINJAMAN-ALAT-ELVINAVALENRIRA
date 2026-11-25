<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian fitur katalog dan peminjaman milik peminjam. */
class PeminjamPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan daftar peminjaman peminjam menampilkan keadaan kosong. */
    public function test_peminjam_peminjaman_page_renders_empty_state(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($peminjam)->get(route('peminjam.peminjaman'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk()
            ->assertSee('Peminjaman Saya')
            ->assertSee('Belum ada peminjaman')
            ->assertSee('Pengembalian')
            ->assertSee('Denda')
            ->assertDontSee('Pengembalian</a>');
    }

    /** Memastikan daftar peminjaman menampilkan tanggal kembali aktual dan denda. */
    public function test_peminjaman_page_shows_actual_return_date_and_fine(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => '2026-10-07',
            'tanggal_kembali_rencana' => '2026-10-10',
            'status' => 'Dikembalikan',
        ]);
        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => '2026-10-12',
            'status' => 'Terlambat',
            'denda' => 15000,
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($peminjam)->get(route('peminjam.peminjaman'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk()
            ->assertSee('12-10-2026')
            ->assertSee('Rp 15.000');
    }

    /** Memastikan peminjam dapat mengajukan pengembalian peminjaman aktif. */
    public function test_peminjam_can_request_return_for_an_active_borrowing(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => '2026-10-07',
            'tanggal_kembali_rencana' => '2026-10-10',
            'status' => 'Dipinjam',
        ]);

        $response = $this->actingAs($peminjam)
            ->post(route('peminjam.peminjaman.ajukan-pengembalian', $peminjaman));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertRedirect(route('peminjam.peminjaman'));
        $this->assertNotNull($peminjaman->fresh()->pengembalian_diajukan_at);

        $this->actingAs($peminjam)
            ->get(route('peminjam.peminjaman'))
            ->assertSee('Menunggu pemeriksaan');

        $petugas = User::factory()->create(['role' => 'petugas']);
        $this->actingAs($petugas)
            ->get(route('petugas.peminjaman.index'))
            ->assertOk()
            ->assertSee('Pengembalian diajukan')
            ->assertSee(route('petugas.pengembalian.create', $peminjaman->id), false);

        $this->actingAs($petugas)
            ->get(route('petugas.pengembalian.index'))
            ->assertOk()
            ->assertSee('Menunggu pemeriksaan')
            ->assertSee(route('petugas.pengembalian.create', $peminjaman->id), false);
    }

    /** Memastikan peminjam tidak dapat mengajukan pengembalian milik pengguna lain. */
    public function test_peminjam_cannot_request_return_for_another_users_borrowing(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $pemilik = User::factory()->create(['role' => 'peminjam']);
        $peminjaman = Peminjaman::create([
            'user_id' => $pemilik->id,
            'tanggal_pinjam' => '2026-10-07',
            'tanggal_kembali_rencana' => '2026-10-10',
            'status' => 'Dipinjam',
        ]);

        $this->actingAs($peminjam)
            ->post(route('peminjam.peminjaman.ajukan-pengembalian', $peminjaman))
            ->assertNotFound();

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertNull($peminjaman->fresh()->pengembalian_diajukan_at);
    }
}
