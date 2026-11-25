<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelas pengujian masuk, penolakan kredensial salah, dan keluar. */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** Memastikan halaman masuk dapat ditampilkan. */
    public function test_login_screen_can_be_rendered(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->get('/login');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $response->assertStatus(200);
    }

    /** Memastikan sesi yang habis menampilkan pesan sebelum masuk kembali. */
    public function test_login_screen_shows_expired_session_status(): void
    {
        $this->withSession([
            'status' => 'Sesi Anda sudah berakhir. Silakan masuk kembali.',
        ])->get('/login')
            ->assertOk()
            ->assertSeeText('Sesi Anda sudah berakhir. Silakan masuk kembali.');
    }

    /** Memastikan kredensial sah dapat masuk melalui halaman login. */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create();

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertAuthenticated();
        $response->assertRedirect(route('peminjam.dashboard', absolute: false));
    }

    /** Memastikan kata sandi salah ditolak saat autentikasi. */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create();

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertGuest();
    }

    /** Memastikan pengguna dapat mengakhiri sesi melalui logout. */
    public function test_users_can_logout(): void
    {
        // Menyiapkan pengguna, data, atau keadaan awal kasus pengujian.
        $user = User::factory()->create();

        // Mengirim permintaan ke rute yang menjadi sasaran pengujian.
        $response = $this->actingAs($user)->post('/logout');

        // Memverifikasi respons atau keadaan yang menjadi ketentuan kasus ini.
        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
