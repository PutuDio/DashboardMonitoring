{{-- resources/views/reports/index.blade.php --}}
{{-- Menggantikan: public/report.php --}}
@extends('layouts.app')

@section('title', 'Laporan Monitoring')

@section('content')
<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="bi bi-file-earmark-bar-graph"></i> Laporan Monitoring Website</h3>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar-range"></i> Periode: <strong>{{ $periodDisplay }}</strong>
            </p>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h6>
        </div>
        <div class="card-body">
            {{-- Quick Filter --}}
            <div class="mb-4">
                <label class="form-label fw-bold mb-2"><i class="bi bi-lightning-fill"></i> Quick Filter:</label>
                <div class="btn-group flex-wrap" role="group">
                    @foreach($quickFilters as $type => $config)
                    <a href="{{ route('reports.index', ['filter_type' => $type]) }}"
                       class="btn btn-outline-{{ $config['class'] }} {{ $filterType === $type ? 'active' : '' }}">
                        <i class="bi bi-{{ $config['icon'] }}"></i> {{ $config['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>

            <hr>

            {{-- Custom Filter --}}
            <div class="mt-3">
                <label class="form-label fw-bold mb-2"><i class="bi bi-sliders"></i> Custom (Bulan & Tahun):</label>
                <form method="GET" action="{{ route('reports.index') }}" class="row g-3">
                    <input type="hidden" name="filter_type" value="custom">
                    <div class="col-md-4">
                        <label class="form-label">Bulan</label>
                        <select name="month" class="form-select form-select-lg">
                            @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                                       '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tahun</label>
                        <select name="year" class="form-select form-select-lg">
                            @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        @foreach([
            ['icon'=>'exclamation-triangle-fill','color'=>'primary','value'=>$statistics['total'],              'label'=>'Total Insiden'],
            ['icon'=>'exclamation-circle-fill',  'color'=>'warning','value'=>$statistics['open'],               'label'=>'Open'],
            ['icon'=>'check-circle-fill',         'color'=>'success','value'=>$statistics['resolved'],           'label'=>'Resolved'],
            ['icon'=>'clock-history',             'color'=>'info',   'value'=>$statistics['avg_resolution_time'].'h','label'=>'Avg Resolution'],
        ] as $card)
        <div class="col-md-3">
            <div class="card border-{{ $card['color'] }} shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-{{ $card['icon'] }} text-{{ $card['color'] }} fs-1"></i>
                    <h4 class="mt-2 text-{{ $card['color'] }}">{{ $card['value'] }}</h4>
                    <p class="mb-0 text-muted">{{ $card['label'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Tren Insiden per Tanggal</h6>
                </div>
                <div class="card-body"><canvas id="incidentTrendChart" height="180"></canvas></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Proporsi Severity</h6>
                </div>
                <div class="card-body"><canvas id="severityChart" height="180"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Jenis Insiden Terbanyak</h6>
                </div>
                <div class="card-body">
                    <canvas id="incidentTypeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-hdd-network"></i> Status Website</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h2 class="text-success mb-2">{{ $websiteStats['active_sites'] ?? 0 }}</h2>
                                <p class="mb-0 text-muted small">Website Aktif</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h2 class="text-danger mb-2">{{ $websiteStats['nonactive_sites'] ?? 0 }}</h2>
                                <p class="mb-0 text-muted small">Website Nonaktif</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <p class="mb-1"><strong>Total Website</strong></p>
                        <h3 class="text-primary">{{ ($websiteStats['active_sites'] ?? 0) + ($websiteStats['nonactive_sites'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Detail Insiden --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0"><i class="bi bi-table"></i> Detail Insiden</h6>
        </div>
        <div class="card-body">
            @if($incidents->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th><th>Website</th><th>Jenis</th><th>Severity</th>
                            <th>Status</th><th>Terdeteksi</th><th>Resolved</th><th>Durasi</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incidents as $no => $inc)
                        @php
                            $dur = \App\Http\Controllers\ReportController::calculateIncidentDuration($inc);
                            $sevClass = ['low'=>'bg-success','medium'=>'bg-warning text-dark','high'=>'bg-danger','critical'=>'bg-dark'][strtolower($inc->severity)] ?? 'bg-secondary';
                            $stClass  = strtolower($inc->status) === 'open' ? 'bg-warning text-dark' : 'bg-success';
                        @endphp
                        <tr>
                            <td>{{ $no + 1 }}</td>
                            <td><small>{{ $inc->name }}</small></td>
                            <td><small>{{ $inc->type }}</small></td>
                            <td><span class="badge {{ $sevClass }}">{{ $inc->severity }}</span></td>
                            <td><span class="badge {{ $stClass }}">{{ $inc->status }}</span></td>
                            <td><small>{{ $inc->created_at->format('d M Y, H:i') }} WIB</small></td>
                            <td>
                                @if($inc->resolved_at)
                                    <small class="text-success">{{ $inc->resolved_at->format('d M Y, H:i') }} WIB</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td><small>{{ $dur['hours'] }}h {{ $dur['minutes'] }}m</small></td>
                            <td>
                                <a href="{{ route('incidents.detail', $inc->incident_id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Tidak ada data insiden pada periode ini.
            </div>
            @endif
        </div>
    </div>

    {{-- Export Buttons --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('reports.pdf', ['start_date'=>$startDate,'end_date'=>$endDate]) }}"
           class="btn btn-danger btn-lg" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
        </a>
        <a href="{{ route('reports.excel', ['start_date'=>$startDate,'end_date'=>$endDate]) }}"
           class="btn btn-success btn-lg">
            <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const reportData = {
    incidentsByDate: @json($chartData['incidents_by_date']),
    severityCount:   @json($chartData['severity_count']),
    typeCount:       @json($chartData['type_count'])
};
// Gunakan class ReportCharts dari assets/js/charts.js yang sama
const reportCharts = new ReportCharts();
reportCharts.init(reportData);
</script>
@endpush
