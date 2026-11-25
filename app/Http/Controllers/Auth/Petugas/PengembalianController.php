<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LogAktivitas;
use Carbon\Carbon;

/** Menampilkan riwayat pengembalian pada antarmuka petugas. */
class PengembalianController extends Controller
{
    /** Menampilkan riwayat pengembalian peminjaman. */
    public function index()
    {
        $pengembalian = Pengembalian::with(['peminjaman.user'])
            ->orderBy('tanggal_dikembalikan', 'desc')
            ->paginate(10);
        return view('admin.pengembalian.index', compact('pengembalian'));
    }

    /** Menyiapkan formulir pengembalian untuk peminjaman terpilih. */
    public function create($peminjaman_id)
    {
        $peminjaman = Peminjaman::with('detailPeminjaman.alat')->findOrFail($peminjaman_id);
        return view('admin.pengembalian.create', compact('peminjaman'));
    }

    /** Memproses validasi, mencatat kondisi tiap alat, lalu mengembalikan stok dalam transaksi. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'peminjaman_id' => 'required|exists:peminjaman,id',
            'tanggal_kembali' => 'required|date',
            'status' => 'required|in:Tepat Waktu,Terlambat,Rusak,Hilang',
            'denda' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'detail.*.kondisi_kembali' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat',
        ]);
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::findOrFail($validated['peminjaman_id']);

            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $validated['peminjaman_id'],
                'tanggal_dikembalikan' => $validated['tanggal_kembali'],
                'status' => $validated['status'],
                'denda' => $validated['denda'] ?? 0,
                'catatan' => $validated['catatan'],
            ]);

            // Simpan kondisi tiap rincian; stok hanya bertambah untuk semua alat yang dikembalikan.
            foreach ($peminjaman->detailPeminjaman as $detail) {
                $kondisiKembali = $validated['detail'][$detail->id]['kondisi_kembali'] ?? 'Baik';
                $detail->update(['kondisi_kembali' => $kondisiKembali]);

                // Tambahkan stok sejumlah unit yang tercatat dalam rincian peminjaman.
                Alat::where('id', $detail->alat_id)->increment('stok', $detail->jumlah);
            }

            // Tandai peminjaman selesai setelah seluruh catatan pengembalian tersimpan.
            $peminjaman->update(['status' => 'Dikembalikan']);

            DB::commit();

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Create Pengembalian',
                'deskripsi' => 'Membuat pengembalian untuk peminjaman ID: ' . $peminjaman->id,
            ]);

            return redirect()->route('pengembalian.index')->with('success', 'Pengembalian berhasil dicatat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat pengembalian: ' . $e->getMessage());
        }
    }

    /** Menampilkan detail pengembalian beserta peminjaman. */
    public function show(Pengembalian $pengembalian)
    {
        $pengembalian->load(['peminjaman.user', 'peminjaman.detailPeminjaman.alat']);
        return view('admin.pengembalian.show', compact('pengembalian'));
    }

    /** Memuat rincian alat peminjaman sebelum menampilkan formulir koreksi. */
    public function edit(Pengembalian $pengembalian)
    {
        $pengembalian->load('peminjaman.detailPeminjaman.alat');
        return view('admin.pengembalian.edit', compact('pengembalian'));
    }

    /** Memperbarui catatan pengembalian. */
    public function update(Request $request, Pengembalian $pengembalian)
    {
        $validated = $request->validate([
            'tanggal_kembali' => 'required|date',
            'status' => 'required|in:Tepat Waktu,Terlambat,Rusak,Hilang',
            'denda' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $validated['tanggal_dikembalikan'] = $validated['tanggal_kembali'];
        unset($validated['tanggal_kembali']);
        $pengembalian->update($validated);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Update Pengembalian',
            'deskripsi' => 'Mengupdate pengembalian ID: ' . $pengembalian->id,
        ]);

        return redirect()->route('pengembalian.index')->with('success', 'Pengembalian berhasil diupdate!');
    }

    /** Menghapus catatan pengembalian. */
    public function destroy(Pengembalian $pengembalian)
    {
        $pengembalian->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Delete Pengembalian',
            'deskripsi' => 'Menghapus pengembalian ID: ' . $pengembalian->id,
        ]);

        return redirect()->route('pengembalian.index')->with('success', 'Pengembalian berhasil dihapus!');
    }

}