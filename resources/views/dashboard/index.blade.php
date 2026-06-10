{{-- resources/views/dashboard/index.blade.php --}}
{{-- Menggantikan: public/index.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid p-4">
    <h3>Selamat Datang, {{ auth()->user()->full_name }}!</h3>
    <p class="text-muted">Sistem ini memantau ketersediaan & keamanan website layanan publik Diskominfo Kota Denpasar.</p>

    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5><i class="bi bi-hdd-network"></i> Website Aktif</h5>
                    <p class="display-6 text-success">{{ $stats['active_sites'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5><i class="bi bi-slash-circle"></i> Website Nonaktif</h5>
                    <p class="display-6 text-secondary">{{ $stats['inactive_sites'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik --}}
    <div class="row mb-4">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5><i class="bi bi-pie-chart"></i> Status Website</h5>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5><i class="bi bi-bar-chart-line"></i> Tren Insiden Mingguan</h5>
                    <canvas id="incidentChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel 5 Website Terbaru --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5><i class="bi bi-list-check"></i> 5 Website Terbaru</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th><th>URL</th><th>Status</th><th>Uptime</th><th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($websites as $w)
                        <tr>
                            <td>{{ $w->name }}</td>
                            <td><a href="{{ $w->url }}" target="_blank">{{ $w->url }}</a></td>
                            <td>
                                <span class="badge {{ $w->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($w->status) }}
                                </span>
                            </td>
                            <td>
                                @if($w->uptime_percentage)
                                    <span class="badge bg-info">{{ $w->uptime_percentage }}%</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($w->response_time_ms > 0)
                                    <span class="badge bg-primary">{{ $w->response_time_ms }} ms</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Belum ada website.
                                @can('manage_websites')
                                    <a href="{{ route('websites.create') }}">Tambah website</a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabel 5 Insiden Terbaru --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5><i class="bi bi-exclamation-triangle"></i> 5 Insiden Terbaru</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Website</th><th>Jenis</th><th>Severity</th><th>Status</th><th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $i)
                        @php
                            $sevClass = ['low'=>'bg-success','medium'=>'bg-warning text-dark','high'=>'bg-danger','critical'=>'bg-dark'][$i->severity] ?? 'bg-secondary';
                            $stClass  = strtolower($i->status) === 'open' ? 'bg-warning text-dark' : 'bg-success';
                        @endphp
                        <tr>
                            <td>{{ $i->name }}</td>
                            <td>{{ $i->type }}</td>
                            <td><span class="badge {{ $sevClass }}">{{ $i->severity }}</span></td>
                            <td><span class="badge {{ $stClass }}">{{ $i->status }}</span></td>
                            <td>{{ $i->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                Tidak ada insiden. Sistem berjalan normal.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Grafik Response Time --}}
    @if(!empty($responseTimeData))
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="bi bi-graph-up"></i> Grafik Waktu Respon Website</h5>
                <select id="websiteSelect" class="form-select w-auto">
                    @foreach($responseTimeData as $siteName => $d)
                        <option value="{{ $siteName }}">{{ $siteName }}</option>
                    @endforeach
                </select>
            </div>
            <canvas id="uptimeChart" height="120"></canvas>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const dashboardData = {
    stats: @json($stats),
    incidentTrend: @json($incidentTrend),
    responseTimeData: @json($responseTimeData)
};
// Gunakan class DashboardCharts dari assets/js/charts.js (sama persis dengan native)
const dashboardCharts = new DashboardCharts();
dashboardCharts.init(dashboardData);
</script>
@endpush
