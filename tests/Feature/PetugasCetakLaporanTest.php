<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasCetakLaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_view_all_returns_and_filter_by_return_date(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $peminjamTerpilih = User::factory()->create(['name' => 'Peminjam Terpilih']);
        $peminjamLain = User::factory()->create(['name' => 'Peminjam Lain']);

        $this->buatPengembalian($peminjamTerpilih, '2026-11-20');
        $this->buatPengembalian($peminjamLain, '2026-11-21');

        $this->actingAs($petugas)
            ->get(route('petugas.laporan.pengembalian'))
            ->assertOk()
            ->assertSee('Peminjam Terpilih')
            ->assertSee('Peminjam Lain')
            ->assertSee('Tanggal cetak:')
            ->assertSee('Laporan Transaksi Peminjaman Alat');

        $this->get(route('petugas.laporan.pengembalian', [
            'status' => 'Dikembalikan',
            'tanggal_mulai' => '2026-11-20',
            'tanggal_selesai' => '2026-11-20',
        ]))
            ->assertOk()
            ->assertSee('Peminjam Terpilih')
            ->assertDontSee('Peminjam Lain')
            ->assertSee('Dikembalikan')
            ->assertSee('Nama petugas: '.$petugas->name);
    }

    public function test_only_petugas_can_access_the_return_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('petugas.laporan.pengembalian'))
            ->assertForbidden();
    }

    private function buatPengembalian(User $peminjam, string $tanggal): void
    {
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => $tanggal,
            'tanggal_kembali_rencana' => $tanggal,
            'status' => 'Dikembalikan',
        ]);

        Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tanggal_kembali' => $tanggal,
            'status' => 'Tepat Waktu',
            'denda' => 0,
        ]);

        $this->assertDatabaseHas('pengembalian', [
            'peminjaman_id' => $peminjaman->id,
            'tanggal_dikembalikan' => $tanggal,
        ]);
    }
}
