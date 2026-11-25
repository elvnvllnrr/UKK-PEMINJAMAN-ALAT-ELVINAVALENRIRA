<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian tampilan serta pembatasan aksi pada area administrator. */
class AdminUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan halaman indeks pengguna dapat dibuka administrator. */
    public function test_admin_users_index_is_accessible_for_admin(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create([
            'username' => 'adminuser2',
            'role' => 'admin',
        ]);
        $regularUser = User::factory()->create(['role' => 'peminjam']);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->get('/admin/users');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk()
            ->assertSee('action="'.route('admin.users.destroy', $regularUser).'"', false)
            ->assertDontSee('action="'.route('admin.users.destroy', $user).'"', false);
    }

    /** Memastikan administrator dapat membuat, memperbarui, dan menghapus pengguna biasa. */
    public function test_admin_can_create_update_and_delete_regular_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'peminjam']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Pengguna Baru',
                'email' => 'baru@example.com',
                'username' => 'penggunabaru',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'peminjam',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'baru@example.com']);

        $this->put(route('admin.users.update', $target), [
            'name' => 'Nama Diperbarui',
            'email' => $target->email,
            'username' => $target->username,
            'role' => 'petugas',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Nama Diperbarui',
            'role' => 'petugas',
        ]);

        $this->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    /** Memastikan akun ber-role admin tidak dapat dihapus. */
    public function test_admin_cannot_delete_an_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $targetAdmin))
            ->assertSessionHas('error', 'Akun dengan role admin tidak dapat dihapus.');

        $this->assertDatabaseHas('users', ['id' => $targetAdmin->id]);
    }
}
