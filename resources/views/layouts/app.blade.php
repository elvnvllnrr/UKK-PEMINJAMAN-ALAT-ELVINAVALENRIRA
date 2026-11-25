<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Peminjaman Alat')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Gaya khusus untuk kerangka halaman aplikasi. --}}
    <style>
        :root {
            --primary-color: #f9b5c9;
            --secondary-color: #f48bb1;
            --success-color: #8dd8c5;
            --warning-color: #f7d9e8;
            --danger-color: #ef5d8d;
            --dark-color: #5d3a4d;
            --light-bg: #fff7fb;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fff0f6 0%, #f9d9e8 40%, #f7c7db 100%);
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #6b4958 0%, #4b2d3f 100%);
            min-height: 100vh;
            color: white;
            box-shadow: 4px 0 15px rgba(77, 38, 58, 0.12);
        }

        .sidebar .nav-link {
            color: #f7dfe8;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            background: var(--light-bg);
            min-height: 100vh;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #f7bfd3 0%, #ee8bb4 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(235, 122, 167, 0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, #7fcdbd 0%, #54bfa2 100%);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f26b8e 0%, #ea4c71 100%);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f7d9e8 0%, #f2b9d2 100%);
            border: none;
            color: #5d3a4d;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #f7bfd3 0%, #ee8bb4 100%);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 500;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #f48bb1;
            box-shadow: 0 0 0 3px rgba(244, 139, 177, 0.12);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-card .icon.bg-gradient-1 {
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
        }

        .stat-card .icon.bg-gradient-2 {
            background: linear-gradient(135deg, #8dd8c5 0%, #61baaa 100%);
        }

        .stat-card .icon.bg-gradient-3 {
            background: linear-gradient(135deg, #f7d9e8 0%, #f3b6d3 100%);
            color: #5d3a4d;
        }

        .stat-card .icon.bg-gradient-4 {
            background: linear-gradient(135deg, #f2bfd7 0%, #e68db8 100%);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">

{{-- Tampilkan blok ini hanya bagi pengguna yang sudah masuk. --}}
            @if(auth()->check())
            <div class="col-md-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white fw-bold">
                        <i class="fas fa-tools me-2"></i>
                        Peminjaman Alat
                    </h4>
                    <small class="text-muted">{{ auth()->user()->role }}</small>
                </div>

{{-- Area navigasi untuk pindah antarhalaman terkait. --}}
                <nav class="nav flex-column">

{{-- Tampilkan blok ini hanya bagi pengguna yang sudah masuk. --}}
                    @if(auth()->user()->role === 'admin')
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.kategori*') ? 'active' : '' }}" href="{{ route('admin.kategori.index') }}">
                            <i class="fas fa-folder"></i> Kategori
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.alat*') ? 'active' : '' }}" href="{{ route('admin.alat.index') }}">
                            <i class="fas fa-box"></i> Data Alat
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.peminjaman*') ? 'active' : '' }}" href="{{ route('admin.peminjaman.index') }}">
                            <i class="fas fa-hand-holding"></i> Peminjaman
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.pengembalian*') ? 'active' : '' }}" href="{{ route('admin.pengembalian.index') }}">
                            <i class="fas fa-undo"></i> Pengembalian
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.log-aktivitas*') ? 'active' : '' }}" href="{{ route('admin.log-aktivitas.index') }}">
                            <i class="fas fa-clock"></i> Log Aktivitas
                        </a>

{{-- Tangani cabang alternatif jika kondisi (auth()->user()->role === 'petugas') terpenuhi. --}}
                    @elseif(auth()->user()->role === 'petugas')
                        <a class="nav-link {{ request()->routeIs('petugas.dashboard') ? 'active' : '' }}" href="{{ route('petugas.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('petugas.alat*') ? 'active' : '' }}" href="{{ route('petugas.alat.index') }}">
                            <i class="fas fa-box"></i> Data Alat
                        </a>
                        <a class="nav-link {{ request()->routeIs('petugas.peminjaman*') ? 'active' : '' }}" href="{{ route('petugas.peminjaman.index') }}">
                            <i class="fas fa-hand-holding"></i> Peminjaman
                        </a>
                        <a class="nav-link {{ request()->routeIs('petugas.pengembalian*') ? 'active' : '' }}" href="{{ route('petugas.pengembalian.index') }}">
                            <i class="fas fa-undo"></i> Pengembalian
                        </a>
                        <a class="nav-link {{ request()->routeIs('petugas.laporan.pengembalian') ? 'active' : '' }}" href="{{ route('petugas.laporan.pengembalian') }}">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </a>

{{-- Tampilkan fallback bila seluruh cabang kondisi sebelumnya tidak terpenuhi. --}}
                    @else
                        <a class="nav-link {{ request()->routeIs('peminjam.dashboard') ? 'active' : '' }}" href="{{ route('peminjam.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('peminjam.katalog') ? 'active' : '' }}" href="{{ route('peminjam.katalog') }}">
                            <i class="fas fa-book-open"></i> Katalog Alat
                        </a>
                        <a class="nav-link {{ request()->routeIs('peminjam.peminjaman') ? 'active' : '' }}" href="{{ route('peminjam.peminjaman') }}">
                            <i class="fas fa-hand-holding"></i> Peminjaman Saya
                        </a>
                    @endif

                    <hr class="border-secondary">
                    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>

{{-- Form untuk mengakhiri sesi pengguna. --}}
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </nav>
            </div>
            @endif

            <div class="{{ auth()->check() ? 'col-md-10' : 'col-12' }} main-content">

{{-- Tampilkan blok ini hanya bagi pengguna yang sudah masuk. --}}
                @if(auth()->check())
                <div class="navbar-top d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        @yield('page-title', 'Dashboard')
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-2">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            <small class="text-muted">{{ auth()->user()->role }}</small>
                        </div>
                    </div>
                </div>
                @endif

{{-- Tampilkan pemberitahuan keberhasilan yang disimpan pada sesi. --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

{{-- Tampilkan blok ini hanya jika kondisi (session('error')) terpenuhi. --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

{{-- Skrip Bootstrap memuat komponen interaktif yang digunakan seluruh halaman aplikasi. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
{{-- Tempatkan aset atau skrip yang didorong oleh halaman turunan. --}}
    @stack('scripts')
</body>
</html>