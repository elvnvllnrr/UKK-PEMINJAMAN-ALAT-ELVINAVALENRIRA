<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian fitur katalog dan peminjaman milik peminjam. */
class PeminjamKatalogTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan katalog peminjam menampilkan alat yang tersedia. */
    public function test_peminjam_katalog_renders_available_tools(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($peminjam)->get(route('peminjam.katalog'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk()
            ->assertSee('Ajukan peminjaman')
            ->assertSee('Belum ada alat tersedia');
    }

    /** Memastikan satu formulir digunakan untuk banyak alat dan satu jadwal bersama. */
    public function test_catalog_uses_one_form_for_multiple_tools_and_one_shared_schedule(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $peminjam = User::factory()->create(['role' => 'peminjam']);
        $kategori = Kategori::create(['nama_kategori' => 'Peralatan']);
        $laptop = Alat::create([
            'nama_alat' => 'Laptop',
            'kategori_id' => $kategori->id,
            'kode_alat' => 'ALT-001',
            'stok' => 3,
        ]);
        $kamera = Alat::create([
            'nama_alat' => 'Kamera',
            'kategori_id' => $kategori->id,
            'kode_alat' => 'ALT-002',
            'stok' => 2,
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($peminjam)->get(route('peminjam.katalog'));

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk()
            ->assertSee('name="alat['.$laptop->id.']"', false)
            ->assertSee('name="alat['.$kamera->id.']"', false)
            ->assertSee('name="tanggal_pinjam"', false)
            ->assertSee('name="tanggal_kembali_rencana"', false)
            ->assertSee('Kirim Permohonan');
    }
}
