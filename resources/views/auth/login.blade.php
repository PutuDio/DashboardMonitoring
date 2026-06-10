{{-- resources/views/auth/login.blade.php --}}
{{-- Menggantikan: public/login.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monitoring Kominfo Denpasar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
<div class="login-container row g-0">
    <div class="col-md-6 login-left">
        <img src="{{ asset('img/monitoring.png') }}" alt="Monitoring Illustration">
    </div>

    <div class="col-md-6 login-right">
        <div class="logo-section">
            <img src="{{ asset('img/Logo_kominfo.png') }}" alt="Logo Kominfo">
            <h4 class="text-primary">Dashboard Monitoring</h4>
            <p class="text-muted small">Sistem Monitoring Website Pemerintah Kota Denpasar</p>
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if(request('error') == 'invalid_session')
            <div class="alert alert-warning">⚠️ Session tidak valid. Silakan login kembali.</div>
        @endif

        {{-- Form Login --}}
        <form method="POST" action="{{ route('login.post') }}" autocomplete="off">
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control @error('username') is-invalid @enderror"
                    placeholder="Masukkan username"
                    maxlength="100"
                    autocomplete="username"
                    value="{{ old('username') }}"
                    required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-login">
                🔐 Login
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">
                © {{ date('Y') }} Dinas Komunikasi, Informasi dan Statistik Kota Denpasar
            </small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
</body>
</html>
