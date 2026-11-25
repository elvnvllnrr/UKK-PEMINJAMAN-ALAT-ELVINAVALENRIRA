@extends('layouts.app')

@section('title', 'Cetak Laporan Transaksi')
@section('page-title', 'Cetak Laporan')

@section('content')
<style>
    @media print {
        @page {
            size: landscape;
            margin: 12mm;
        }

        body {
            background: #fff !important;
            color: #000 !important;
            min-height: auto !important;
        }

        .sidebar,
        .navbar-top,
        .no-print {
            display: none !important;
        }

        .container-fluid,
        .container-fluid > .row,
        .main-content {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .card {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .table {
            width: 100% !important;
            font-size: 8pt;
        }

        .table th,
        .table td {
            padding: 5px !important;
            border: 1px solid #777 !important;
        }

        .table thead {
            display: table-header-group;
            color: #000 !important;
            background: #eee !important;
        }

        tr {
            break-inside: avoid;
        }
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-1">Laporan Transaksi Peminjaman Alat</h1>
        <p class="text-muted mb-0">Laporan peminjaman dan pengembalian dari data aplikasi.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-2" aria-hidden="true"></i>Cetak Laporan
    </button>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form action="{{ route('petugas.laporan.pengembalian') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold">Status peminjaman</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua data</option>
                    @foreach(['Menunggu Persetujuan', 'Dipinjam', 'Terlambat', 'Dikembalikan', 'Ditolak'] as $pilihanStatus)
                        <option value="{{ $pilihanStatus }}" @selected($status === $pilihanStatus)>{{ $pilihanStatus }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3">
                <label for="tanggal_mulai" class="form-label fw-semibold">Tanggal mulai</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="form-control">
                @error('tanggal_mulai')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3">
                <label for="tanggal_selesai" class="form-label fw-semibold">Tanggal selesai</label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ $tanggalSelesai }}" class="form-control">
                @error('tanggal_selesai')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1" aria-hidden="true"></i>Terapkan Filter
                </button>
                <a href="{{ route('petugas.laporan.pengembalian') }}" class="btn btn-outline-secondary">Tampilkan Semua</a>
            </div>
        </form>
    </div>
</div>

<section class="card">
    <div class="card-body">
        <header class="text-center mb-4">
            <h2 class="h4 fw-bold mb-1">Laporan Transaksi Peminjaman Alat</h2>
            <p class="mb-1">Tanggal cetak: {{ now()->format('d-m-Y H:i') }}</p>
            <p class="mb-1">Nama petugas: {{ auth()->user()->name }}</p>
            <p class="mb-0">
                Filter:
                {{ $status ?: 'Semua status' }}
                @if($tanggalMulai || $tanggalSelesai)
                    · {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : 'Awal' }}
                    s.d. {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : 'Sekarang' }}
                @else
                    · Semua tanggal
                @endif
            </p>
        </header>

        <h3 class="h5 fw-bold mt-4">Daftar Peminjaman</h3>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Peminjaman</th>
                        <th>Peminjam</th>
                        <th>Alat</th>
                        <th>Tanggal Pinjam</th>
                        <th>Rencana Kembali</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <th>Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($peminjaman as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->user->name ?? '-' }}</td>
                            <td>
                                @forelse($item->detailPeminjaman as $detail)
                                    <div>{{ $detail->alat->nama_alat ?? 'Alat tidak tersedia' }} × {{ $detail->jumlah }}</div>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d-m-Y') }}</td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($item->tanggal_kembali_rencana)->format('d-m-Y') }}</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->petugas->name ?? '-' }}</td>
                            <td class="text-nowrap">Rp {{ number_format($item->pengembalian->denda ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada data peminjaman untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3 class="h5 fw-bold mt-4">Daftar Pengembalian</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Peminjaman</th>
                        <th>Peminjam</th>
                        <th>Tanggal Dikembalikan</th>
                        <th>Status Pengembalian</th>
                        <th>Petugas</th>
                        <th>Denda Keterlambatan</th>
                        <th>Denda Kerusakan/Kehilangan</th>
                        <th>Total Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengembalian as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->peminjaman->id }}</td>
                            <td>{{ $item->peminjaman->user->name ?? '-' }}</td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($item->tanggal_dikembalikan)->format('d-m-Y') }}</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->petugas->name ?? '-' }}</td>
                            <td class="text-nowrap">Rp {{ number_format($item->denda - $item->denda_kerusakan, 0, ',', '.') }}</td>
                            <td class="text-nowrap">Rp {{ number_format($item->denda_kerusakan, 0, ',', '.') }}</td>
                            <td class="text-nowrap">Rp {{ number_format($item->denda, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada data pengembalian untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
