<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian pembatasan halaman berdasarkan peran pengguna. */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan administrator dapat membuka dasbor administrasi. */
    public function test_admin_dashboard_accessible_for_admin(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create([
            'username' => 'adminuser',
            'role' => 'admin',
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertOk();
    }

    /** Memastikan petugas menerima 403 saat membuka halaman admin. */
    public function test_petugas_cannot_access_admin_page(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create([
            'username' => 'petugasuser',
            'role' => 'petugas',
        ]);

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->get('/admin/peminjaman');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_borrower_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['role' => 'peminjam']);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_role_protected_pages(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_staff_or_borrower_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/petugas/dashboard')->assertForbidden();
        $this->get('/peminjam/dashboard')->assertForbidden();
    }
}
