{{-- Gunakan kerangka halaman aplikasi yang menjadi pembungkus tampilan ini. --}}
@extends('layouts.app')

{{-- Tetapkan judul halaman pada tab atau jendela browser. --}}
@section('title', 'Manajemen Users')

{{-- Tampilkan judul utama untuk bagian daftar pengguna. --}}
@section('page-title', 'Manajemen Users')

{{-- Isi utama tampilan: daftar pengguna. --}}
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users me-2"></i>Daftar Users</span>
        <a href="{{ route('admin.users.create') }}" class="btn btn-light">
            <i class="fas fa-plus me-2"></i>Tambah User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">

{{-- Tabel ringkasan data pada daftar pengguna. --}}
            <table class="table table-hover">

{{-- Header kolom tabel. --}}
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

{{-- Baris tabel hasil data pada daftar pengguna. --}}
                <tbody>

{{-- Ulangi $index => $user dari $users untuk menampilkan daftar pengguna; saat koleksi kosong, gunakan state kosong. --}}
                    @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>

{{-- Pilih tampilan atau aksi sesuai peran pengguna pada daftar pengguna. --}}
                                @if($user->role === 'admin')
                                    <span class="badge bg-danger">Admin</span>

{{-- Tangani cabang alternatif jika kondisi ($user->role === 'petugas') terpenuhi. --}}
                                @elseif($user->role === 'petugas')
                                    <span class="badge bg-warning text-dark">Petugas</span>

{{-- Tampilkan fallback bila seluruh cabang kondisi sebelumnya tidak terpenuhi. --}}
                                @else
                                    <span class="badge bg-success">Peminjam</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>

{{-- Form untuk mengirim permintaan penghapusan pada daftar pengguna. --}}
                                @if($user->role !== 'admin')
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
{{-- Tampilkan status kosong ketika koleksi tidak memiliki data. --}}
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data user</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>
@endsection
