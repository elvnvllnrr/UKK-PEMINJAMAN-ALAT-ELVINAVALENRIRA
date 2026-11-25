<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Menyimpan status, tenggat, rincian, dan pengembalian peminjaman. */
class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    protected $fillable = [
        'user_id',
        'petugas_id',
        'tanggal_pinjam',
        'tanggal_kembali_rencana',
        'pengembalian_diajukan_at',
        'status',
        'catatan',
    ];

    protected $casts = [
        'pengembalian_diajukan_at' => 'datetime',
    ];

    /** Menghubungkan peminjaman dengan pengguna peminjam. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Menghubungkan peminjaman dengan petugas yang menyetujuinya. */
    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /** Mengambil seluruh rincian alat dalam peminjaman. */
    public function detailPeminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'peminjaman_id');
    }

    /** Menghubungkan peminjaman dengan catatan pengembaliannya. */
    public function pengembalian()
    {
        return $this->hasOne(Pengembalian::class, 'peminjaman_id');
    }

    /** Menghitung hari melewati tenggat untuk tanggal pengembalian. */
    public function hitungHariTerlambat(string $tanggalKembali): int
    {
        $tanggalBatas = Carbon::parse($this->tanggal_kembali_rencana)->startOfDay();
        $tanggalAktual = Carbon::parse($tanggalKembali)->startOfDay();

        return $tanggalAktual->greaterThan($tanggalBatas)
        ? (int) $tanggalBatas->diffInDays($tanggalAktual)
        : 0;
    }
}