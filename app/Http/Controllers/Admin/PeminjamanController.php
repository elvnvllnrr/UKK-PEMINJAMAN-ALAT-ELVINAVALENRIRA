<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\DetailPeminjaman;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Mengelola pengajuan, persetujuan, penolakan, dan status peminjaman. */
class PeminjamanController extends Controller
{
    /** Memfilter daftar peminjaman menurut status atau role. */
    public function index()
    {
        $this->perbaruiStatusTerlambat();

        $query = Peminjaman::with(['user', 'detailPeminjaman.alat', 'pengembalian'])
            ->orderByDesc('tanggal_pinjam');
        $status = request('status');

        if ($status === 'Pengembalian Diajukan') {
            $query->whereNotNull('pengembalian_diajukan_at')
                ->whereDoesntHave('pengembalian');
        } elseif ($status === 'Aktif') {
            $query->whereIn('status', ['Dipinjam', 'Terlambat']);
        } elseif (in_array($status, ['Menunggu Persetujuan', 'Dipinjam', 'Terlambat', 'Dikembalikan', 'Ditolak'], true)) {
            $query->where('status', $status);
        }

        $peminjaman = $query->paginate(10)->withQueryString();
        if (auth()->user()->role === 'petugas') {
            return view('petugas.index', compact('peminjaman'));
        }

        $statusCounts = Peminjaman::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $ringkasan = [
            'total' => Peminjaman::count(),
            'menunggu' => (int) $statusCounts->get('Menunggu Persetujuan', 0),
            'aktif' => (int) $statusCounts->get('Dipinjam', 0) + (int) $statusCounts->get('Terlambat', 0),
            'terlambat' => (int) $statusCounts->get('Terlambat', 0),
            'pengembalian_diajukan' => Peminjaman::whereIn('status', ['Dipinjam', 'Terlambat'])
            ->whereNotNull('pengembalian_diajukan_at')
            ->whereDoesntHave('pengembalian')
            ->count(),
        ];

        return view('admin.peminjaman.index', compact('peminjaman', 'ringkasan'));
    }

    /** Memperbarui status terlambat dan menampilkan detail peminjaman. */
    public function show(Peminjaman $peminjaman)
    {
        $this->perbaruiStatusTerlambat();
        $peminjaman->refresh();
        $peminjaman->load(['user', 'detailPeminjaman.alat', 'pengembalian']);
        if (auth()->user()->role === 'petugas') {
            return view('petugas.show', compact('peminjaman'));
        }

        return view('admin.peminjaman.show', compact('peminjaman'));
    }

    /** Menampilkan riwayat peminjaman milik pengguna masuk. */
    public function myPeminjaman()
    {
        $this->perbaruiStatusTerlambat();

        $peminjaman = Peminjaman::where('user_id', auth()->id())
            ->with(['detailPeminjaman.alat', 'pengembalian'])
            ->orderBy('tanggal_pinjam', 'desc')
            ->paginate(10);

        return view('peminjam.peminjaman', compact('peminjaman'));
    }

    /** Mencatat pengajuan pengembalian peminjaman yang masih aktif. */
    public function ajukanPengembalian(Peminjaman $peminjaman)
    {
        abort_unless($peminjaman->user_id === auth()->id(), 404);
        $berhasil = DB::transaction(function () use ($peminjaman) {
            $peminjaman = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            if (! in_array($peminjaman->status, ['Dipinjam', 'Terlambat'], true)
            || $peminjaman->pengembalian_diajukan_at
            || $peminjaman->pengembalian()->exists()) {
                return false;
            }

            $peminjaman->update(['pengembalian_diajukan_at' => now()]);
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Ajukan Pengembalian',
                'deskripsi' => 'Mengajukan pengembalian untuk peminjaman ID: '.$peminjaman->id,
            ]);

            return true;
        });

        if (! $berhasil) {
            return redirect()->route('peminjam.peminjaman')
                ->with('error', 'Pengajuan pengembalian tidak dapat diproses untuk peminjaman ini.');
        }

        return redirect()->route('peminjam.peminjaman')
            ->with('success', 'Pengajuan pengembalian berhasil dikirim. Silakan serahkan barang kepada petugas untuk diperiksa.');
    }

    /** Mengunci stok, membuat peminjaman, dan menyimpan rinciannya. */
    public function ajukanPeminjaman(Request $request)
    {
        $validated = $request->validate([
            'tanggal_pinjam' => 'required|date|after_or_equal:today',
            'tanggal_kembali_rencana' => 'required|date|after:tanggal_pinjam|after_or_equal:today',
            'catatan' => 'nullable|string|max:2000',
            'alat' => 'required|array',
            'alat.*' => 'required|integer|min:0',
        ]);

        $pilihanAlat = collect($validated['alat'])
            ->filter(fn ($jumlah) => (int) $jumlah > 0);

        if ($pilihanAlat->isEmpty()) {
            throw ValidationException::withMessages([
                'alat' => 'Pilih minimal satu alat dengan jumlah lebih dari nol.',
            ]);
        }
        DB::transaction(function () use ($validated, $pilihanAlat) {
            $detailAlat = [];
            foreach ($pilihanAlat as $alatId => $jumlah) {
                $alat = Alat::whereKey($alatId)->lockForUpdate()->first();
                if (! $alat || $alat->stok < (int) $jumlah) {
                    throw ValidationException::withMessages([
                        'alat' => 'Stok alat berubah atau tidak mencukupi. Periksa stok lalu ajukan kembali.',
                    ]);
                }
                $detailAlat[] = [$alat, (int) $jumlah];
            }

            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali_rencana' => $validated['tanggal_kembali_rencana'],
                'status' => 'Menunggu Persetujuan',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($detailAlat as [$alat, $jumlah]) {
                DetailPeminjaman::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alat->id,
                    'jumlah' => $jumlah,
                    'kondisi_pinjam' => 'Baik',
                ]);
            }

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Ajukan Peminjaman',
                'deskripsi' => 'Mengajukan peminjaman ID: '.$peminjaman->id,
            ]);
        });

        return redirect()->route('peminjam.peminjaman')->with('success', 'Peminjaman berhasil diajukan! Menunggu persetujuan petugas.');
    }

    /** Memeriksa stok lalu mengurangi stok saat menyetujui peminjaman. */
    public function approve(Peminjaman $peminjaman)
    {
        DB::transaction(function () use ($peminjaman) {
            $peminjaman = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();
            if ($peminjaman->status !== 'Menunggu Persetujuan') {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan ini sudah diproses.',
                ]);
            }
            if (today()->lt($peminjaman->tanggal_pinjam)) {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan hanya dapat disetujui pada atau setelah tanggal mulai pinjam.',
                ]);
            }

            $peminjaman->load('detailPeminjaman');
            foreach ($peminjaman->detailPeminjaman as $detail) {
                $alat = Alat::whereKey($detail->alat_id)->lockForUpdate()->firstOrFail();
                if ($alat->stok < $detail->jumlah) {
                    throw ValidationException::withMessages([
                        'status' => "Stok {$alat->nama_alat} tidak mencukupi. Tersedia {$alat->stok} unit; permohonan memerlukan {$detail->jumlah}.",
                    ]);
                }
                // Kurangi stok sesuai jumlah unit pada rincian yang disetujui.
                $alat->decrement('stok', $detail->jumlah);
            }

            $peminjaman->update([
                'status' => today()->gt($peminjaman->tanggal_kembali_rencana) ? 'Terlambat' : 'Dipinjam',
                'petugas_id' => auth()->id(),
            ]);
        });

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Approve Peminjaman',
            'deskripsi' => 'Menyetujui peminjaman ID: '.$peminjaman->id,
        ]);

        return back()->with('success', 'Peminjaman disetujui dan stok berhasil diperiksa.');
    }

    /** Mengubah permohonan menunggu menjadi ditolak. */
    public function reject(Peminjaman $peminjaman)
    {
        DB::transaction(function () use ($peminjaman) {
            $peminjaman = Peminjaman::whereKey($peminjaman->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($peminjaman->status !== 'Menunggu Persetujuan') {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan ini sudah diproses.',
                ]);
            }

            $peminjaman->update(['status' => 'Ditolak']);
        });

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Reject Peminjaman',
            'deskripsi' => 'Menolak peminjaman ID: '.$peminjaman->id,
        ]);

        return back()->with('success', 'Peminjaman ditolak!');
    }

    /** Mengubah peminjaman aktif yang melewati tenggat menjadi terlambat. */
    private function perbaruiStatusTerlambat(): void
    {
        Peminjaman::where('status', 'Dipinjam')
            ->whereDate('tanggal_kembali_rencana', '<', today())
            ->update(['status' => 'Terlambat']);
    }
}









