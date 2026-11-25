<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Mengelola katalog, CRUD, stok awal, dan foto alat. */
class DataAlatController extends Controller
{
    /** Menampilkan alat beserta kategori dengan paginasi. */
    public function index()
    {
        $alats = Alat::with('kategori')->orderBy('nama_alat')->paginate(12);

        return view('admin.alat.index', compact('alats'));
    }

    /** Menyiapkan pilihan kategori pada formulir alat baru. */
    public function create()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.alat.create', compact('kategoris'));
    }

    /** Memvalidasi unggahan dan menyimpan alat beserta stok awal. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'kode_alat' => 'required|string|unique:alat,kode_alat|max:50',
            'deskripsi' => 'nullable|string',
            'stok' => 'required|integer|min:0',
            'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('foto_alat', 'public');
        }

        Alat::create($validated);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Create Alat',
            'deskripsi' => 'Menambah alat: '.$validated['nama_alat'],
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil ditambahkan!');
    }

    /** Memuat kategori lalu menampilkan detail alat. */
    public function show(Alat $alat)
    {
        $alat->load('kategori');

        return view('admin.alat.show', compact('alat'));
    }

    /** Menyiapkan alat dan daftar kategori pada formulir perubahan. */
    public function edit(Alat $alat)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('admin.alat.edit', compact('alat', 'kategoris'));
    }

    /** Memperbarui data atau foto alat dan menghapus foto lama bila diganti. */
    public function update(Request $request, Alat $alat)
    {
        $validated = $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'kode_alat' => 'required|string|unique:alat,kode_alat,'.$alat->id.'|max:50',
            'deskripsi' => 'nullable|string',
            'stok' => 'required|integer|min:0',
            'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            if ($alat->foto) {
                Storage::disk('public')->delete($alat->foto);
            }
            $validated['foto'] = $request->file('foto')->store('foto_alat', 'public');
        }

        $alat->update($validated);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Update Alat',
            'deskripsi' => 'Mengupdate alat: '.$validated['nama_alat'],
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil diupdate!');
    }

    /** Menghapus data dan berkas foto alat. */
    public function destroy(Alat $alat)
    {
        if ($alat->detailPeminjaman()->exists()) {
            return back()->with('error', 'Alat tidak dapat dihapus karena sudah tercatat dalam transaksi.');
        }

        if ($alat->foto) {
            Storage::disk('public')->delete($alat->foto);
        }

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Delete Alat',
            'deskripsi' => 'Menghapus alat: '.$alat->nama_alat,
        ]);

        $alat->delete();

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil dihapus!');
    }

    /** Menampilkan alat yang stoknya tersedia. */
    public function katalog()
    {
        $alats = Alat::with('kategori')->where('stok', '>', 0)->orderBy('nama_alat')->get();

        return view('peminjam.katalog', compact('alats'));
    }
}









