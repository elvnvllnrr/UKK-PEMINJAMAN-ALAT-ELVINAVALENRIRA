<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Gaya khusus untuk login pengguna. --}}
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #fff1f6 0%, #f8d8e6 40%, #f2bfd7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
            max-width: 500px;
            width: min(100%, 500px);
            margin: 20px auto;
        }
        form {
            width: 100%;
        }
        .input-group {
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header i {
            font-size: 60px;
            background: linear-gradient(135deg, #f7bfd3 0%, #ee8bb4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        .login-header h3 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #718096;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #f48bb1;
            box-shadow: 0 0 0 3px rgba(244, 139, 177, 0.12);
        }
        .btn-login {
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(235, 122, 167, 0.35);
        }
        .input-group-text {
            background: linear-gradient(135deg, #f7a9c3 0%, #eb7aa7 100%);
            color: white;
            border: none;
            border-radius: 10px 0 0 10px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-tools"></i>
            <h3>Sistem Peminjaman Alat</h3>
            <p>Silakan login untuk melanjutkan</p>
        </div>

{{-- Tampilkan pemberitahuan keberhasilan yang disimpan pada sesi. --}}
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-info" role="status">
                {{ session('status') }}
            </div>
        @endif

{{-- Tampilkan ringkasan kesalahan setelah validasi formulir gagal. --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif

{{-- Form untuk mengirim kredensial login pengguna. --}}
        <form method="POST" action="{{ route('login', absolute: false) }}">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" value="{{ old('username') }}" required autofocus placeholder="Masukkan username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" required placeholder="Masukkan password">
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="text-center mt-4">
            <span class="text-muted">Belum punya akun peminjam?</span>
            <a href="{{ route('register', absolute: false) }}" class="fw-semibold text-decoration-none">Daftar sekarang</a>
        </div>
    </div>
</body>
</html>