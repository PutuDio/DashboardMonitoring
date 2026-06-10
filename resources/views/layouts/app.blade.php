{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Monitoring Kominfo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/export.css') }}">

    @stack('styles')
</head>
<body>

    <div class="p-3 d-flex align-items-center justify-content-center border-bottom fixed-top"
         style="background: #0056b3; min-height: 57px;">
        <img src="{{ asset('img/Lambang_Kota_Denpasar.png') }}" alt="Logo" style="height:36px;">
        <span class="ms-2 fw-bold text-white small">MONITORING KOMINFOS KOTA DENPASAR</span>
    </div>
    
{{-- ── SIDEBAR ──────────────────────────────────────────────────── --}}
<nav class="sidebar" id="sidebar">

    {{-- Logo --}}
    <div class="p-3 border-bottom">
        <span class="fs-6 fw-bold d-block mt-3 text-primary">
            Dinas Komunikasi Informasi dan Statistik
        </span>
    </div>

    {{-- Menu --}}
    <ul class="nav flex-column p-2 mt-1">

        {{-- Dashboard --}}
        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        {{-- Insiden — tampil untuk admin, operator --}}
        @can('view_incidents')
        <li class="nav-item">
            <a href="{{ route('incidents.index') }}"
               class="nav-link {{ request()->routeIs('incidents*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-exclamation-triangle me-2"></i> Insiden
            </a>
        </li>
        @endcan

        {{-- Laporan --}}
        @can('view_reports')
        <li class="nav-item">
            <a href="{{ route('reports.index') }}"
               class="nav-link {{ request()->routeIs('reports*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-file-bar-graph me-2"></i> Laporan
            </a>
        </li>
        @endcan

        {{-- Admin section --}}
        @if(auth()->user()->isAdmin())
        <li class="nav-item mt-2">
            <small class="text-muted px-2 fw-semibold" style="font-size:0.7rem; letter-spacing:.05em;">ADMIN</small>
        </li>
        <li class="nav-item">
            <a href="{{ route('websites.index') }}"
               class="nav-link {{ request()->routeIs('websites*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-hdd-network me-2"></i> Website
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('users.index') }}"
               class="nav-link {{ request()->routeIs('users*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-people me-2"></i> Kelola User
            </a>
        </li>
        @endif

        {{-- Akun section --}}
        <li class="nav-item mt-2">
            <small class="text-muted px-2 fw-semibold" style="font-size:0.7rem; letter-spacing:.05em;">AKUN</small>
        </li>
        <li class="nav-item">
            <a href="{{ route('settings.index') }}"
               class="nav-link {{ request()->routeIs('settings*') ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1">
                <i class="bi bi-gear me-2"></i> Pengaturan
            </a>
        </li>
        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="nav-link btn btn-link text-danger w-100 text-start rounded mb-1">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </button>
            </form>
        </li>
    </ul>

    {{-- Footer user info --}}
    <div class="p-3 border-top mt-auto" style="position:absolute; bottom:0; width:100%;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-4 text-muted"></i>
            <div>
                <div class="small fw-semibold text-dark" style="line-height:1.2">
                    {{ auth()->user()->full_name }}
                </div>
                <span class="badge bg-secondary" style="font-size:0.65rem;">
                    {{ strtoupper(auth()->user()->role) }}
                </span>
            </div>
        </div>
    </div>
</nav>

{{-- ── MAIN CONTENT ─────────────────────────────────────────────── --}}
<div class="main-content">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mx-3 mt-3" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

{{-- ── SCRIPTS ──────────────────────────────────────────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/charts.js') }}"></script>

<script>
    window.Laravel = { csrfToken: '{{ csrf_token() }}' };
</script>

@stack('scripts')
</body>
</html>