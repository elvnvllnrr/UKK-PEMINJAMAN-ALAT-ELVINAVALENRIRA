{{-- Gunakan kerangka halaman aplikasi yang menjadi pembungkus tampilan ini. --}}
@extends('layouts.app')

{{-- Tetapkan judul halaman pada tab atau jendela browser. --}}
@section('title', 'Dashboard Petugas')

{{-- Tampilkan judul utama untuk bagian ringkasan tugas petugas. --}}
@section('page-title', 'Tugas Petugas')

{{-- Isi utama tampilan: ringkasan tugas petugas. --}}
@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Tugas hari ini</h1>
    <p class="text-muted mb-0">Mulai dari permohonan dan pengembalian yang menunggu tindakan Anda.</p>
</div>

<section class="mb-4" aria-labelledby="data-tersimpan-heading">
    <h2 id="data-tersimpan-heading" class="h5 fw-bold mb-3">Ringkasan data tersimpan</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('petugas.alat.index') }}" class="text-decoration-none">
                <div class="stat-card h-100">
                    <small class="text-muted">Jenis alat</small>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="h2 fw-bold mb-0 text-dark">{{ $stats['total_alat'] }}</span>
                        <i class="fas fa-toolbox text-primary fs-3" aria-hidden="true"></i>
                    </div>
                    <small class="text-primary">Lihat inventaris <i class="fas fa-arrow-right ms-1"></i></small>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('petugas.peminjaman.index') }}" class="text-decoration-none">
                <div class="stat-card h-100">
                    <small class="text-muted">Total peminjaman</small>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="h2 fw-bold mb-0 text-dark">{{ $stats['total_peminjaman'] }}</span>
                        <i class="fas fa-clipboard-list text-primary fs-3" aria-hidden="true"></i>
                    </div>
                    <small class="text-primary">Lihat riwayat <i class="fas fa-arrow-right ms-1"></i></small>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('petugas.pengembalian.index') }}" class="text-decoration-none">
                <div class="stat-card h-100">
                    <small class="text-muted">Total pengembalian</small>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="h2 fw-bold mb-0 text-dark">{{ $stats['total_pengembalian'] }}</span>
                        <i class="fas fa-undo text-primary fs-3" aria-hidden="true"></i>
                    </div>
                    <small class="text-primary">Lihat riwayat <i class="fas fa-arrow-right ms-1"></i></small>
                </div>
            </a>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">Angka tugas di bawah hanya menghitung transaksi yang masih menunggu tindakan atau belum selesai.</p>
</section>

<h2 class="h5 fw-bold mb-3">Pekerjaan yang perlu ditindaklanjuti</h2>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ route('petugas.peminjaman.index', ['status' => 'Menunggu Persetujuan']) }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <small class="text-muted">Permohonan menunggu</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="h2 fw-bold mb-0 text-dark">{{ $stats['menunggu_persetujuan'] }}</span>
                    <i class="fas fa-clipboard-list text-warning fs-3" aria-hidden="true"></i>
                </div>
                <small class="text-primary">Periksa dan putuskan <i class="fas fa-arrow-right ms-1"></i></small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('petugas.pengembalian.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <small class="text-muted">Pengembalian menunggu</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="h2 fw-bold mb-0 text-dark">{{ $stats['pengembalian_diajukan'] }}</span>
                    <i class="fas fa-undo text-primary fs-3" aria-hidden="true"></i>
                </div>
                <small class="text-primary">Periksa kondisi barang <i class="fas fa-arrow-right ms-1"></i></small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('petugas.peminjaman.index', ['status' => 'Terlambat']) }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <small class="text-muted">Peminjaman terlambat</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="h2 fw-bold mb-0 text-dark">{{ $stats['terlambat'] }}</span>
                    <i class="fas fa-clock text-danger fs-3" aria-hidden="true"></i>
                </div>
                <small class="text-primary">Lihat daftar <i class="fas fa-arrow-right ms-1"></i></small>
            </div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
{{-- Bagian konten khusus untuk ringkasan tugas petugas. --}}
        <section class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clipboard-list me-2"></i>Permohonan baru</span>
                <a href="{{ route('petugas.peminjaman.index', ['status' => 'Menunggu Persetujuan']) }}" class="btn btn-sm btn-light">Lihat semua</a>
            </div>
            <div class="card-body">

{{-- Ulangi $item dari $peminjamanMenunggu untuk menampilkan ringkasan tugas petugas; saat koleksi kosong, gunakan state kosong. --}}
                @forelse($peminjamanMenunggu as $item)
                    <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $item->user->name ?? 'Peminjam tidak tersedia' }}</div>
                            <div class="small text-muted">{{ $item->detailPeminjaman->count() }} jenis alat · kembali {{ \Carbon\Carbon::parse($item->tanggal_kembali_rencana)->format('d-m-Y') }}</div>
                        </div>
                        <a href="{{ route('petugas.peminjaman.show', $item) }}" class="btn btn-sm btn-outline-primary">Periksa</a>
                    </div>
{{-- Tampilkan status kosong ketika koleksi tidak memiliki data. --}}
                @empty
                    <p class="text-muted mb-0">Tidak ada permohonan yang menunggu.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-lg-6">
{{-- Bagian konten khusus untuk ringkasan tugas petugas. --}}
        <section class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-undo me-2"></i>Barang untuk diperiksa</span>
                <a href="{{ route('petugas.pengembalian.index') }}" class="btn btn-sm btn-light">Lihat semua</a>
            </div>
            <div class="card-body">

{{-- Ulangi $item dari $pengembalianMenunggu untuk menampilkan ringkasan tugas petugas; saat koleksi kosong, gunakan state kosong. --}}
                @forelse($pengembalianMenunggu as $item)
                    <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $item->user->name ?? 'Peminjam tidak tersedia' }}</div>
                            <div class="small text-muted">

{{-- Ulangi $detail dari $item->detailPeminjaman untuk membangun isi berulang pada ringkasan tugas petugas. --}}
                                @foreach($item->detailPeminjaman as $detail)
                                    {{ $detail->alat->nama_alat ?? 'Alat tidak tersedia' }} × {{ $detail->jumlah }}@if(!$loop->last), @endif
                                @endforeach
                            </div>
                            <small class="text-muted">Diajukan {{ $item->pengembalian_diajukan_at->format('d-m-Y H:i') }}</small>
                        </div>
                        <a href="{{ route('petugas.pengembalian.create', $item->id) }}" class="btn btn-sm btn-primary">Periksa</a>
                    </div>
{{-- Tampilkan status kosong ketika koleksi tidak memiliki data. --}}
                @empty
                    <p class="text-muted mb-0">Tidak ada pengembalian yang menunggu.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('petugas.alat.index') }}" class="btn btn-outline-secondary"><i class="fas fa-toolbox me-2"></i>Lihat ketersediaan alat</a>
</div>
@endsection
