<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Menyimpan akun, role, dan relasi peminjaman pengguna. */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Menentukan atribut datetime yang dikonversi otomatis. */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /** Mengambil semua peminjaman milik pengguna. */
    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'user_id');
    }

    /** Mengambil peminjaman yang disetujui oleh petugas ini. */
    public function peminjamanDisetujui()
    {
        return $this->hasMany(Peminjaman::class, 'petugas_id');
    }

    /** Mengambil pengembalian yang diproses oleh petugas ini. */
    public function pengembalianDiproses()
    {
        return $this->hasMany(Pengembalian::class, 'petugas_id');
    }
    /** Memeriksa apakah role pengguna adalah admin. */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /** Memeriksa apakah role pengguna adalah petugas. */
    public function isPetugas()
    {
        return $this->role === 'petugas';
    }

    /** Memeriksa apakah role pengguna adalah peminjam. */
    public function isPeminjam()
    {
        return $this->role === 'peminjam';
    }
}