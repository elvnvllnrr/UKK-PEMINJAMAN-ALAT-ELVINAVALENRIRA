<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Models\LogAktivitas;

/** Mengelola kategori alat beserta alat yang terkait. */
class KategoriController extends Controller
{
    /** Menampilkan kategori dengan jumlah alat per kategori. */
    public function index()
    {
        $kategoris = Kategori::withCount('alat')->orderBy('nama_kategori')->paginate(10);
        return view('admin.kategori.index', compact('kategoris'));
    }

    /** Menampilkan formulir kategori baru. */
    public function create()
    {
        return view('admin.kategori.create');
    }

    /** Memvalidasi dan menyimpan kategori baru. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori,nama_kategori',
            'deskripsi' => 'nullable|string|max:500',
        ]);

        Kategori::create($validated);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Create Kategori',
            'deskripsi' => 'Membuat kategori: ' . $validated['nama_kategori'],
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan!');
    }

    /** Menampilkan kategori pada formulir perubahan. */
    public function edit(Kategori $kategori)
    {
        return view('admin.kategori.edit', compact('kategori'));
    }

    /** Memperbarui nama kategori. */
    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori,nama_kategori,' . $kategori->id,
            'deskripsi' => 'nullable|string|max:500',
        ]);

        $kategori->update($validated);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Update Kategori',
            'deskripsi' => 'Mengupdate kategori: ' . $validated['nama_kategori'],
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diupdate!');
    }

    /** Mencegah penghapusan kategori yang masih dipakai alat. */
    public function destroy(Kategori $kategori)
    {
        if ($kategori->alat()->count() > 0) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki alat!');
        }

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Delete Kategori',
            'deskripsi' => 'Menghapus kategori: ' . $kategori->nama_kategori,
        ]);

        $kategori->delete();
        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus!');
    }
}