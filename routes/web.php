<?php

use App\Http\Controllers\Admin\DataAlatController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\LoginLogoutController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\PeminjamanController;
use App\Http\Controllers\Admin\PengembalianController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\Petugas\CetakLaporanController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Alat;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Route;

// Login & Logout
Route::get('/login', [LoginLogoutController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginLogoutController::class, 'login']);
Route::post('/logout', [LoginLogoutController::class, 'logout'])->name('logout');

// Tampilkan dan proses registrasi akun peminjam bagi pengunjung yang belum login.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// Menampilkan halaman landing untuk pengunjung.
Route::get('/', function () {
    return view('landing');
})->name('dashboard');

// ==================== ADMIN ROUTES ====================
// Membatasi seluruh rute pengelolaan pada pengguna admin.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Menandai peminjaman yang telah melewati tenggat dan merangkum data dashboard.
    Route::get('/dashboard', function () {
        Peminjaman::where('status', 'Dipinjam')
            ->whereDate('tanggal_kembali_rencana', '<', today())
            ->update(['status' => 'Terlambat']);

        $stats = [
            'permohonan_menunggu' => Peminjaman::where('status', 'Menunggu Persetujuan')->count(),
            'pengembalian_menunggu' => Peminjaman::whereIn('status', ['Dipinjam', 'Terlambat'])
                ->whereNotNull('pengembalian_diajukan_at')
                ->whereDoesntHave('pengembalian')
                ->count(),
            'peminjaman_aktif' => Peminjaman::whereIn('status', ['Dipinjam', 'Terlambat'])->count(),
            'alat_tersedia' => Alat::where('stok', '>', 0)->count(),
        ];
        $peminjamanMenunggu = Peminjaman::with(['user', 'detailPeminjaman.alat'])
            ->where('status', 'Menunggu Persetujuan')
            ->latest()
            ->take(5)
            ->get();
        $pengembalianMenunggu = Peminjaman::with(['user', 'detailPeminjaman.alat'])
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->whereNotNull('pengembalian_diajukan_at')
            ->whereDoesntHave('pengembalian')
            ->orderBy('pengembalian_diajukan_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'peminjamanMenunggu', 'pengembalianMenunggu'));
    })->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('kategori', KategoriController::class);
    Route::resource('alat', DataAlatController::class);
    Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'show']);
    Route::resource('pengembalian', PengembalianController::class)->except(['create', 'store']);
    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');

});

// ==================== PETUGAS ROUTES ====================
// Membatasi seluruh rute operasional pada pengguna petugas.
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    // Menandai keterlambatan dan merangkum aktivitas operasional petugas.
    Route::get('/dashboard', function () {
        Peminjaman::where('status', 'Dipinjam')
            ->whereDate('tanggal_kembali_rencana', '<', today())
            ->update(['status' => 'Terlambat']);

        $stats = [
            'total_alat' => Alat::count(),
            'total_peminjaman' => Peminjaman::count(),
            'total_pengembalian' => Pengembalian::count(),
            'peminjaman_aktif' => Peminjaman::whereIn('status', ['Dipinjam', 'Terlambat'])->count(),
            'menunggu_persetujuan' => Peminjaman::where('status', 'Menunggu Persetujuan')->count(),
            'terlambat' => Peminjaman::where('status', 'Terlambat')->count(),
            'pengembalian_diajukan' => Peminjaman::whereIn('status', ['Dipinjam', 'Terlambat'])
                ->whereNotNull('pengembalian_diajukan_at')
                ->whereDoesntHave('pengembalian')
                ->count(),
        ];

        $peminjamanMenunggu = Peminjaman::with(['user', 'detailPeminjaman'])
            ->where('status', 'Menunggu Persetujuan')
            ->latest()
            ->take(5)
            ->get();
        $pengembalianMenunggu = Peminjaman::with(['user', 'detailPeminjaman.alat'])
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->whereNotNull('pengembalian_diajukan_at')
            ->whereDoesntHave('pengembalian')
            ->orderBy('pengembalian_diajukan_at')
            ->take(5)
            ->get();

        return view('petugas.dashboard', compact('stats', 'peminjamanMenunggu', 'pengembalianMenunggu'));
    })->name('dashboard');

    Route::resource('alat', DataAlatController::class)->only(['index', 'show']);
    Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'show']);
    Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
    Route::post('/peminjaman/{peminjaman}/reject', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');

    Route::get('/pengembalian/create/{peminjaman_id}', [PengembalianController::class, 'create'])->name('pengembalian.create');
    Route::post('/pengembalian', [PengembalianController::class, 'store'])->name('pengembalian.store');
    Route::resource('pengembalian', PengembalianController::class)->only(['index', 'show']);
    Route::get('/laporan/pengembalian', [CetakLaporanController::class, 'index'])->name('laporan.pengembalian');
});

// ==================== PEMINJAM ROUTES ====================
// Membatasi seluruh rute peminjaman pada pengguna peminjam.
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    // Memperbarui status milik pengguna dan merangkum peminjaman aktif.
    Route::get('/dashboard', function () {
        Peminjaman::where('user_id', auth()->id())
            ->where('status', 'Dipinjam')
            ->whereDate('tanggal_kembali_rencana', '<', today())
            ->update(['status' => 'Terlambat']);

        $stats = [
            'menunggu_persetujuan' => Peminjaman::where('user_id', auth()->id())
                ->where('status', 'Menunggu Persetujuan')
                ->count(),
            'peminjaman_aktif' => Peminjaman::where('user_id', auth()->id())
                ->whereIn('status', ['Dipinjam', 'Terlambat'])
                ->count(),
            'pengembalian_diproses' => Peminjaman::where('user_id', auth()->id())
                ->whereIn('status', ['Dipinjam', 'Terlambat'])
                ->whereNotNull('pengembalian_diajukan_at')
                ->whereDoesntHave('pengembalian')
                ->count(),
        ];
        $peminjamanTerbaru = Peminjaman::where('user_id', auth()->id())
            ->with(['detailPeminjaman.alat', 'pengembalian'])
            ->latest('tanggal_pinjam')
            ->take(5)
            ->get();

        return view('peminjam.dashboard', compact('stats', 'peminjamanTerbaru'));
    })->name('dashboard');

    Route::get('/katalog', [DataAlatController::class, 'katalog'])->name('katalog');
    Route::get('/peminjaman', [PeminjamanController::class, 'myPeminjaman'])->name('peminjaman');
    Route::post('/peminjaman', [PeminjamanController::class, 'ajukanPeminjaman'])->name('peminjaman.store');
    Route::post('/peminjaman/{peminjaman}/ajukan-pengembalian', [PeminjamanController::class, 'ajukanPengembalian'])->name('peminjaman.ajukan-pengembalian');
});

require __DIR__.'/auth.php';
