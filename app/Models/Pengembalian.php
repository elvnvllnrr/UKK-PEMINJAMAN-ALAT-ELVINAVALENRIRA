<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Menyimpan tanggal, status, denda, dan catatan pengembalian. */
class Pengembalian extends Model
{
    use HasFactory;

    protected $table = 'pengembalian';

    protected $fillable = [
        'peminjaman_id',
        'petugas_id',
        'tanggal_dikembalikan',
        'tanggal_kembali',
        'status',
        'denda',
        'denda_kerusakan',
        'catatan',
    ];

    /** Menghubungkan data dengan peminjaman. */
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    /** Menghubungkan catatan dengan petugas yang memproses pengembalian. */
    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /** Menjaga kompatibilitas nama input lama dengan kolom tanggal skema LKPD. */
    public function getTanggalKembaliAttribute(): ?string
    {
        return $this->attributes['tanggal_dikembalikan'] ?? null;
    }

    /** Menyimpan input lama ke kolom tanggal_dikembalikan. */
    public function setTanggalKembaliAttribute(string $tanggal): void
    {
        $this->attributes['tanggal_dikembalikan'] = $tanggal;
    }
}