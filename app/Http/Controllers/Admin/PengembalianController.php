<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Memproses pencatatan, koreksi, dan penghapusan pengembalian. */
class PengembalianController extends Controller
{
    /** Memuat daftar pengembalian dan pengajuan yang masih perlu diproses. */
    public function index()
    {
        Peminjaman::where('status', 'Dipinjam')
            ->whereDate('tanggal_kembali_rencana', '<', today())
            ->update(['status' => 'Terlambat']);

        $pengembalian = Pengembalian::with(['peminjaman.user'])
            ->orderBy('tanggal_dikembalikan', 'desc')
            ->paginate(10);
        $pengembalianMenunggu = Peminjaman::with(['user', 'detailPeminjaman.alat'])
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->whereNotNull('pengembalian_diajukan_at')
            ->whereDoesntHave('pengembalian')
            ->orderBy('pengembalian_diajukan_at')
            ->get();
        $peminjamanBelumTercatat = Peminjaman::with('user')
            ->where('status', 'Dikembalikan')
            ->whereDoesntHave('pengembalian')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.pengembalian.index', compact('pengembalian', 'pengembalianMenunggu', 'peminjamanBelumTercatat'));
    }

    /** Memastikan peminjaman memenuhi syarat sebelum formulir pengembalian dibuka. */
    public function create(Request $request, ?string $peminjaman_id = null)
    {
        $peminjaman_id ??= $request->query('peminjaman_id');

        if (! $peminjaman_id) {
            foreach ($request->query() as $key => $value) {
                if (is_numeric($key)) {
                    $peminjaman_id = $key;
                    break;
                }

                if (is_numeric($value)) {
                    $peminjaman_id = (string) $value;
                    break;
                }
            }
        }
        abort_unless($peminjaman_id && ctype_digit((string) $peminjaman_id), 404);

        $peminjaman = Peminjaman::with('user', 'detailPeminjaman.alat')->findOrFail($peminjaman_id);
        $statusAktif = in_array($peminjaman->status, ['Dipinjam', 'Terlambat'], true);
        $pengembalianLegacy = $peminjaman->status === 'Dikembalikan';
        abort_unless(
        (($statusAktif && $peminjaman->pengembalian_diajukan_at) || $pengembalianLegacy)
        && ! $peminjaman->pengembalian()->exists(),
        404
        );

        return view('admin.pengembalian.create', compact('peminjaman'));
    }

    /** Memvalidasi kondisi alat, menghitung denda, dan mencatat pengembalian atomik. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'peminjaman_id' => 'required|exists:peminjaman,id',
            'tanggal_kembali' => 'required|date|before_or_equal:today',
            'denda_kerusakan' => 'nullable|numeric|min:0|max:9999999999.99',
            'catatan' => 'nullable|string|max:2000',
            'detail' => 'required|array',
            'detail.*.kondisi_kembali' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Hilang',
        ]);
        DB::transaction(function () use ($validated) {
            $peminjaman = Peminjaman::whereKey($validated['peminjaman_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $statusAktif = in_array($peminjaman->status, ['Dipinjam', 'Terlambat'], true);
            $statusLegacy = $peminjaman->status === 'Dikembalikan';

            if (
                (! $statusAktif && ! $statusLegacy)
                || ($statusAktif && ! $peminjaman->pengembalian_diajukan_at)
                || $peminjaman->pengembalian()->exists()
            ) {
                throw ValidationException::withMessages([
                    'peminjaman_id' => 'Peminjaman ini tidak dapat diproses sebagai pengembalian.',
                ]);
            }

            $peminjaman->load('detailPeminjaman');
            $kondisiKembali = [];
            $idDetailValid = $peminjaman->detailPeminjaman->modelKeys();
            $idDetailDikirim = array_map('intval', array_keys($validated['detail']));

            if (array_diff($idDetailDikirim, $idDetailValid)) {
                throw ValidationException::withMessages([
                    'detail' => 'Data kondisi alat tidak sesuai dengan peminjaman ini.',
                ]);
            }

            foreach ($peminjaman->detailPeminjaman as $detail) {
                $kondisi = $validated['detail'][$detail->id]['kondisi_kembali'] ?? null;
                if (! $kondisi) {
                    throw ValidationException::withMessages([
                        'detail' => 'Pilih kondisi untuk setiap alat yang dikembalikan.',
                    ]);
                }

                $kondisiKembali[$detail->id] = $kondisi;
            }

            $adaMasalah = collect($kondisiKembali)->contains(fn ($kondisi) => $kondisi !== 'Baik');
            $dendaKerusakan = (float) ($validated['denda_kerusakan'] ?? 0);

            if ($adaMasalah && ($validated['denda_kerusakan'] ?? null) === null) {
                throw ValidationException::withMessages([
                    'denda_kerusakan' => 'Masukkan nominal denda kerusakan atau kehilangan, gunakan 0 jika tidak ada.',
                ]);
            }
            if (! $adaMasalah && $dendaKerusakan > 0) {
                throw ValidationException::withMessages([
                    'denda_kerusakan' => 'Denda kerusakan hanya dapat dicatat jika ada barang rusak atau hilang.',
                ]);
            }
            if ($dendaKerusakan > 0 && blank($validated['catatan'] ?? null)) {
                throw ValidationException::withMessages([
                    'catatan' => 'Jelaskan alasan denda kerusakan atau kehilangan.',
                ]);
            }

            $tanggalKembali = $validated['tanggal_kembali'];
            $dendaKeterlambatan = $peminjaman->hitungHariTerlambat($tanggalKembali)
                * (int) config('peminjaman.denda_keterlambatan_per_hari');
            $totalDenda = $dendaKeterlambatan + $dendaKerusakan;
            if ($totalDenda > 9999999999.99) {
                throw ValidationException::withMessages([
                    'denda_kerusakan' => 'Total denda melebihi batas maksimum yang dapat dicatat.',
                ]);
            }

            foreach ($peminjaman->detailPeminjaman as $detail) {
                $kondisi = $kondisiKembali[$detail->id];
                $detail->update(['kondisi_kembali' => $kondisi]);

                if ($statusAktif && $kondisi === 'Baik') {
                    // Tambah stok kembali hanya jika alat dikembalikan dalam kondisi baik.
                    Alat::whereKey($detail->alat_id)->increment('stok', $detail->jumlah);
                }
            }

            $adaHilang = in_array('Hilang', $kondisiKembali, true);
            $adaRusak = collect($kondisiKembali)->contains(
                fn ($kondisi) => in_array($kondisi, ['Rusak Ringan', 'Rusak Berat'], true)
            );
            $statusPengembalian = $adaHilang
                ? 'Hilang'
                : ($adaRusak
                    ? 'Rusak'
                    : ($tanggalKembali > $peminjaman->tanggal_kembali_rencana ? 'Terlambat' : 'Tepat Waktu'));

            Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'petugas_id' => auth()->id(),
                'tanggal_dikembalikan' => $tanggalKembali,
                'status' => $statusPengembalian,
                'denda' => $totalDenda,
                'denda_kerusakan' => $dendaKerusakan,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            if ($statusAktif) {
                $peminjaman->update(['status' => 'Dikembalikan']);
            }

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Proses Pengembalian',
                'deskripsi' => 'Memproses pengembalian untuk peminjaman ID: '.$peminjaman->id,
            ]);
        });

        $route = auth()->user()->role === 'petugas' ? 'petugas.pengembalian.index' : 'admin.pengembalian.index';

        return redirect()->route($route)->with('success', 'Pengembalian berhasil dicatat.');
    }

    /** Memuat peminjam dan rincian alat untuk halaman detail. */
    public function show(Pengembalian $pengembalian)
    {
        $pengembalian->load(['peminjaman.user', 'peminjaman.detailPeminjaman.alat']);

        return view('admin.pengembalian.show', compact('pengembalian'));
    }

    /** Memuat rincian peminjaman pada formulir koreksi pengembalian. */
    public function edit(Pengembalian $pengembalian)
    {
        $pengembalian->load('peminjaman.detailPeminjaman.alat');

        return view('admin.pengembalian.edit', compact('pengembalian'));
    }

    /** Menghitung ulang denda dan status berdasarkan koreksi pengembalian. */
    public function update(Request $request, Pengembalian $pengembalian)
    {
        $validated = $request->validate([
            'tanggal_kembali' => 'required|date|before_or_equal:today',
            'denda_kerusakan' => 'nullable|numeric|min:0|max:9999999999.99',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $pengembalian->load('peminjaman.detailPeminjaman');
        $kondisi = $pengembalian->peminjaman->detailPeminjaman
            ->pluck('kondisi_kembali')
            ->filter();
        $statusMasalahLama = in_array($pengembalian->status, ['Rusak', 'Hilang'], true);
        $adaMasalah = $statusMasalahLama || $kondisi->contains(fn ($nilai) => $nilai !== 'Baik');
        $dendaKerusakan = (float) ($validated['denda_kerusakan'] ?? 0);

        if ($adaMasalah && ($validated['denda_kerusakan'] ?? null) === null) {
            throw ValidationException::withMessages([
                'denda_kerusakan' => 'Masukkan nominal denda kerusakan atau kehilangan, gunakan 0 jika tidak ada.',
            ]);
        }
        if (! $adaMasalah && $dendaKerusakan > 0) {
            throw ValidationException::withMessages([
                'denda_kerusakan' => 'Denda kerusakan hanya dapat dicatat jika ada barang rusak atau hilang.',
            ]);
        }
        if ($dendaKerusakan > 0 && blank($validated['catatan'] ?? null)) {
            throw ValidationException::withMessages([
                'catatan' => 'Jelaskan alasan denda kerusakan atau kehilangan.',
            ]);
        }

        $dendaKeterlambatan = $pengembalian->peminjaman
            ->hitungHariTerlambat($validated['tanggal_kembali'])
        * (int) config('peminjaman.denda_keterlambatan_per_hari');
        $totalDenda = $dendaKeterlambatan + $dendaKerusakan;
        if ($totalDenda > 9999999999.99) {
            throw ValidationException::withMessages([
                'denda_kerusakan' => 'Total denda melebihi batas maksimum yang dapat dicatat.',
            ]);
        }

        $adaHilang = $kondisi->contains('Hilang')
        || ($kondisi->isEmpty() && $pengembalian->status === 'Hilang');
        $adaRusak = $kondisi->contains(fn ($nilai) => in_array($nilai, ['Rusak Ringan', 'Rusak Berat'], true))
        || ($kondisi->isEmpty() && $pengembalian->status === 'Rusak');
        $statusPengembalian = $adaHilang
        ? 'Hilang'
        : ($adaRusak
        ? 'Rusak'
        : ($validated['tanggal_kembali'] > $pengembalian->peminjaman->tanggal_kembali_rencana
        ? 'Terlambat'
        : 'Tepat Waktu'));

        $pengembalian->update([
            'tanggal_dikembalikan' => $validated['tanggal_kembali'],
            'status' => $statusPengembalian,
            'denda' => $totalDenda,
            'denda_kerusakan' => $dendaKerusakan,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Update Pengembalian',
            'deskripsi' => 'Mengupdate pengembalian ID: '.$pengembalian->id,
        ]);

        return redirect()->route('admin.pengembalian.index')->with('success', 'Pengembalian berhasil diupdate!');
    }

    /** Mengembalikan stok alat baik lalu menghapus catatan pengembalian. */
    public function destroy(Pengembalian $pengembalian)
    {
        DB::transaction(function () use ($pengembalian) {
            $pengembalian = Pengembalian::whereKey($pengembalian->id)
                ->lockForUpdate()
                ->firstOrFail();
            $peminjaman = Peminjaman::whereKey($pengembalian->peminjaman_id)
                ->lockForUpdate()
                ->firstOrFail();
            // Pengembalian lama hanya boleh diproses jika masih terhubung dengan pengajuan.
            if (! $peminjaman->pengembalian_diajukan_at) {
                throw ValidationException::withMessages([
                    'pengembalian' => 'Catatan pengembalian lama tidak dapat dihapus agar stok tetap akurat.',
                ]);
            }
            $peminjaman->load('detailPeminjaman');

            foreach ($peminjaman->detailPeminjaman as $detail) {
                if ($detail->kondisi_kembali === 'Baik') {
                    $alat = Alat::whereKey($detail->alat_id)->lockForUpdate()->firstOrFail();
                    if ($alat->stok < $detail->jumlah) {
                        throw ValidationException::withMessages([
                            'pengembalian' => "Catatan tidak dapat dihapus karena {$alat->nama_alat} sudah dipinjam kembali.",
                        ]);
                    }
                    // Kurangi stok agar catatan pengembalian lama tidak meninggalkan saldo ganda.
                    $alat->decrement('stok', $detail->jumlah);
                }

                $detail->update(['kondisi_kembali' => null]);
            }

            $peminjaman->update([
                'status' => today()->gt($peminjaman->tanggal_kembali_rencana) ? 'Terlambat' : 'Dipinjam',
                'pengembalian_diajukan_at' => null,
            ]);
            $pengembalian->delete();
        });

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Delete Pengembalian',
            'deskripsi' => 'Menghapus pengembalian ID: '.$pengembalian->id,
        ]);
        $route = auth()->user()->role === 'petugas' ? 'petugas.pengembalian.index' : 'admin.pengembalian.index';

        return redirect()->route($route)->with('success', 'Pengembalian berhasil dihapus!');
    }
}









