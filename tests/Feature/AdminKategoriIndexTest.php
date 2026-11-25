<?php

namespace Tests\Feature;

use App\Models\Kategori;
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

    /** Memastikan nama kategori yang hanya berisi angka ditolak. */
    public function test_admin_cannot_create_category_with_numeric_only_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.kategori.store'), ['nama_kategori' => '123'])
            ->assertSessionHasErrors('nama_kategori');

        $this->assertDatabaseMissing('kategori', ['nama_kategori' => '123']);

        $kategori = Kategori::create(['nama_kategori' => 'Peralatan']);
        $this->put(route('admin.kategori.update', $kategori), ['nama_kategori' => '456'])
            ->assertSessionHasErrors('nama_kategori');

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'nama_kategori' => 'Peralatan',
        ]);
    }

    /** Memastikan nama kategori alfanumerik tetap dapat disimpan saat diperbarui. */
    public function test_admin_can_update_category_with_alphanumeric_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);

        $this->actingAs($admin)
            ->put(route('admin.kategori.update', $kategori), ['nama_kategori' => '3D Tools'])
            ->assertRedirect(route('admin.kategori.index'));

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'nama_kategori' => '3D Tools',
        ]);
    }
}
