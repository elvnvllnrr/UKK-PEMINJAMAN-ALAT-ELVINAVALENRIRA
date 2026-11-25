<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\LogAktivitas;

/** Mengelola akun pengguna beserta peran dan kredensial. */
class UserController extends Controller
{
    /** Menampilkan pengguna dengan urutan pendaftaran terbaru. */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /** Menampilkan formulir pengguna baru. */
    public function create()
    {
        return view('admin.users.create');
    }

    /** Memvalidasi data akun lalu menyimpan kata sandi terenkripsi. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Create User',
            'deskripsi' => 'Membuat user baru: ' . $user->username,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    /** Menampilkan formulir perubahan pengguna. */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /** Memperbarui profil dan mengganti kata sandi bila diisi. */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'username' => 'required|string|unique:users,username,' . $user->id . '|max:50',
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        $user->update($validated);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Update User',
            'deskripsi' => 'Mengupdate user: ' . $user->username,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate!');
    }

    /** Menghapus pengguna beserta catatan aktivitasnya. */
    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Akun dengan role admin tidak dapat dihapus.');
        }

        if ($user->is(auth()->user())) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($user->peminjaman()->exists()) {
            return back()->with('error', 'User tidak dapat dihapus karena memiliki riwayat transaksi.');
        }

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Delete User',
            'deskripsi' => 'Menghapus user: ' . $user->username,
        ]);

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus!');
    }
}