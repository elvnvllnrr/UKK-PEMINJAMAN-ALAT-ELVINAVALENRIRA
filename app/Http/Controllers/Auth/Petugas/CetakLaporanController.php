<?php

namespace App\Http\Controllers\Auth\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Menampilkan dan memfilter laporan transaksi untuk dicetak. */
class CetakLaporanController extends Controller
{
    /** Menampilkan laporan transaksi dengan filter status dan rentang tanggal. */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:Menunggu Persetujuan,Dipinjam,Terlambat,Dikembalikan,Ditolak'],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (
            ! empty($validated['tanggal_mulai'])
            && ! empty($validated['tanggal_selesai'])
            && $validated['tanggal_mulai'] > $validated['tanggal_selesai']
        ) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            ]);
        }

        $status = $validated['status'] ?? null;
        $tanggalMulai = $validated['tanggal_mulai'] ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;
        $filterStatus = fn ($query) => $query->when($status, fn ($query, $status) => $query->where('status', $status));
        $filterPeminjaman = function ($query) use ($filterStatus, $tanggalMulai, $tanggalSelesai) {
            $filterStatus($query);
            $query
                ->when($tanggalMulai, fn ($query, $tanggal) => $query->whereDate('tanggal_pinjam', '>=', $tanggal))
                ->when($tanggalSelesai, fn ($query, $tanggal) => $query->whereDate('tanggal_pinjam', '<=', $tanggal));
        };

        $peminjaman = Peminjaman::with(['user', 'petugas', 'detailPeminjaman.alat', 'pengembalian'])
            ->where($filterPeminjaman)
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id')
            ->get();
        $pengembalian = Pengembalian::with([
            'petugas',
            'peminjaman.user',
            'peminjaman.petugas',
            'peminjaman.detailPeminjaman.alat',
        ])
            ->whereHas('peminjaman', $filterStatus)
            ->when($tanggalMulai, fn ($query, $tanggal) => $query->whereDate('tanggal_dikembalikan', '>=', $tanggal))
            ->when($tanggalSelesai, fn ($query, $tanggal) => $query->whereDate('tanggal_dikembalikan', '<=', $tanggal))
            ->orderByDesc('tanggal_dikembalikan')
            ->orderByDesc('id')
            ->get();

        return view('petugas.laporan.pengembalian', compact(
            'peminjaman',
            'pengembalian',
            'status',
            'tanggalMulai',
            'tanggalSelesai',
        ));
    }
}
