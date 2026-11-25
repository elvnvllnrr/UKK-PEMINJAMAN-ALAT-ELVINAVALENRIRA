{{-- Gunakan kerangka halaman aplikasi yang menjadi pembungkus tampilan ini. --}}
@extends('layouts.app')

{{-- Tetapkan judul halaman pada tab atau jendela browser. --}}
@section('title', 'Katalog Alat')

{{-- Tampilkan judul utama untuk bagian katalog alat yang dapat dipinjam. --}}
@section('page-title', 'Ajukan Peminjaman')

{{-- Isi utama tampilan: katalog alat yang dapat dipinjam. --}}
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Ajukan peminjaman</h1>
        <p class="text-muted mb-0">Pilih jumlah alat yang dibutuhkan, lalu kirim satu permohonan.</p>
    </div>
    <a href="{{ route('peminjam.peminjaman') }}" class="btn btn-outline-primary">
        <i class="fas fa-list me-2"></i>Peminjaman Saya
    </a>
</div>

{{-- Tampilkan ringkasan kesalahan setelah validasi formulir gagal. --}}
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">Permohonan belum dapat dikirim.</div>
        <ul class="mb-0">

{{-- Ulangi $error dari $errors->all() untuk membangun isi berulang pada katalog alat yang dapat dipinjam. --}}
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Form untuk menyimpan data baru pada katalog alat yang dapat dipinjam. --}}
<form action="{{ route('peminjam.peminjaman.store') }}" method="POST">
    @csrf
    <div class="row g-3">

{{-- Ulangi $alat dari $alats untuk menampilkan katalog alat yang dapat dipinjam; saat koleksi kosong, gunakan state kosong. --}}
        @forelse($alats as $alat)
            <div class="col-sm-6 col-xl-4">
                <article class="card h-100 overflow-hidden">

{{-- Tampilkan blok ini hanya jika kondisi ($alat->foto) terpenuhi. --}}
                    @if($alat->foto)
                        <img src="{{ asset('storage/' . $alat->foto) }}" class="w-100" style="height: 190px; object-fit: cover;" alt="{{ $alat->nama_alat }}">

{{-- Tampilkan fallback bila seluruh cabang kondisi sebelumnya tidak terpenuhi. --}}
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-secondary" style="height: 190px;">
                            <i class="fas fa-toolbox fa-3x" aria-hidden="true"></i>
                        </div>
                    @endif

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h2 class="h5 fw-bold mb-1">{{ $alat->nama_alat }}</h2>
                                <small class="text-muted">{{ $alat->kategori->nama_kategori ?? 'Tanpa kategori' }} · {{ $alat->kode_alat }}</small>
                            </div>
                            <span class="badge bg-success">Stok {{ $alat->stok }}</span>
                        </div>

                        <p class="text-muted small flex-grow-1 mb-2">{{ $alat->deskripsi ?: 'Tidak ada deskripsi.' }}</p>
                        <div class="small mb-3">Kondisi: <strong>{{ $alat->kondisi }}</strong></div>

                        <label for="jumlah-alat-{{ $alat->id }}" class="form-label mb-1">Jumlah yang dipinjam</label>
                        <input
                            id="jumlah-alat-{{ $alat->id }}"
                            type="number"
                            name="alat[{{ $alat->id }}]"
                            class="form-control"
                            min="0"
                            max="{{ $alat->stok }}"
                            value="{{ old('alat.' . $alat->id, 0) }}"
                            inputmode="numeric"
                            aria-describedby="bantuan-alat-{{ $alat->id }}"
                        >
                        <small id="bantuan-alat-{{ $alat->id }}" class="text-muted mt-1">Isi 0 jika tidak memilih alat ini.</small>
                    </div>
                </article>
            </div>
{{-- Tampilkan status kosong ketika koleksi tidak memiliki data. --}}
        @empty
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-box-open fa-3x text-muted mb-3" aria-hidden="true"></i>
                        <h2 class="h5">Belum ada alat tersedia</h2>
                        <p class="text-muted mb-0">Silakan periksa kembali nanti.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

{{-- Tampilkan blok ini hanya jika kondisi ($alats->isNotEmpty()) terpenuhi. --}}
    @if($alats->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-calendar-alt me-2"></i>Jadwal Peminjaman
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="tanggal-pinjam" class="form-label">Tanggal mulai</label>
                        <input id="tanggal-pinjam" type="date" name="tanggal_pinjam" class="form-control" value="{{ old('tanggal_pinjam', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal-kembali" class="form-label">Rencana kembali</label>
                        <input id="tanggal-kembali" type="date" name="tanggal_kembali_rencana" class="form-control" value="{{ old('tanggal_kembali_rencana', now()->addDay()->toDateString()) }}" required>
                    </div>
                    <div class="col-12">
                        <label for="catatan" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                        <textarea id="catatan" name="catatan" class="form-control" rows="2" maxlength="2000" placeholder="Keterangan tambahan bila diperlukan">{{ old('catatan') }}</textarea>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                    <p class="text-muted small mb-0">Stok baru dikurangi setelah petugas menyetujui dan menyerahkan alat.</p>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Kirim Permohonan
                    </button>
                </div>
            </div>
        </div>
    @endif
</form>

@endsection
