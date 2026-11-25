<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian tampilan serta pembatasan aksi pada area administrator. */
class AdminKategoriIndexTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan halaman indeks kategori dapat dibuka administrator. */
    public function test_admin_kategori_index_is_accessible_for_admin(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create([
            'username' => 'adminuser3',
            'role' => 'admin',
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->get('/admin/kategori');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk();
    }

    /** Memastikan administrator dapat menambah kategori lalu dialihkan ke indeks. */
    public function test_admin_can_create_category_and_is_redirected_to_index(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create([
            'username' => 'adminuser4',
            'role' => 'admin',
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->post('/admin/kategori', [
            'nama_kategori' => 'Elektronik',
            'deskripsi' => 'Peralatan elektronik',
        ]);

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertRedirect(route('admin.kategori.index'));
        $this->assertDatabaseHas('kategori', [
            'nama_kategori' => 'Elektronik',
        ]);
    }
}
