{{-- resources/views/incidents/index.blade.php --}}
{{-- Menggantikan: public/incident.php --}}
@extends('layouts.app')

@section('title', 'Daftar Insiden')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="bi bi-exclamation-triangle"></i> Daftar Insiden</h3>
            <p class="text-muted mb-0">Berikut adalah daftar insiden terbaru dari hasil monitoring.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-danger fs-6"><i class="bi bi-exclamation-circle"></i> {{ $stats['open'] }} Open</span>
            <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> {{ $stats['resolved'] }} Resolved</span>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                @foreach([
                    ['id' => 'filterSeverity', 'label' => 'Filter Severity', 'opts' => ['Low','Medium','High','Critical']],
                    ['id' => 'filterStatus',   'label' => 'Filter Status',   'opts' => ['Open','Resolved']],
                    ['id' => 'filterType',     'label' => 'Filter Jenis',    'opts' => ['Content Change','SSL Certificate Expired','Server Downtime','Slow Response','HTTP Error']],
                ] as $f)
                <div class="col-md-3">
                    <label class="form-label small text-muted">{{ $f['label'] }}</label>
                    <select class="form-select" id="{{ $f['id'] }}">
                        <option value="">Semua</option>
                        @foreach($f['opts'] as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
                <div class="col-md-3">
                    <label class="form-label small text-muted">&nbsp;</label>
                    <button class="btn btn-secondary w-100" onclick="resetFilter()">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle" id="incidentTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Website</th>
                            <th width="15%">Jenis</th>
                            <th width="10%">Severity</th>
                            <th width="10%">Status</th>
                            <th width="15%">Terdeteksi</th>
                            <th width="15%">Resolved</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $i => $inc)
                        @php
                            $sevClass = ['low'=>'bg-success','medium'=>'bg-warning text-dark','high'=>'bg-danger','critical'=>'bg-dark'][strtolower($inc->severity)] ?? 'bg-secondary';
                            $stClass  = strtolower($inc->status) === 'open' ? 'bg-warning text-dark' : 'bg-success';
                            $stIcon   = strtolower($inc->status) === 'open' ? 'exclamation-circle' : 'check-circle';
                        @endphp
                        <tr data-severity="{{ $inc->severity }}" data-status="{{ $inc->status }}" data-type="{{ $inc->type }}">
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $inc->name }}</strong>
                                <br><small class="text-muted text-truncate d-inline-block" style="max-width:200px">{{ $inc->url }}</small>
                            </td>
                            <td><span class="badge bg-info text-dark">{{ $inc->type }}</span></td>
                            <td><span class="badge {{ $sevClass }}">{{ $inc->severity }}</span></td>
                            <td><span class="badge {{ $stClass }}"><i class="bi bi-{{ $stIcon }}"></i> {{ $inc->status }}</span></td>
                            <td>
                                <small>{{ $inc->created_at->format('d M Y') }}</small>
                                <br><small class="text-muted">{{ $inc->created_at->format('H:i') }} WIB</small>
                            </td>
                            <td>
                                @if($inc->resolved_at)
                                    <small class="text-success">{{ $inc->resolved_at->format('d M Y') }}</small>
                                    <br><small class="text-muted">{{ $inc->resolved_at->format('H:i') }} WIB</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('incidents.detail', $inc->incident_id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data insiden.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    @if($incidents->isNotEmpty())
    <div class="row mt-4">
        @foreach([
            ['icon'=>'exclamation-triangle-fill','color'=>'warning','value'=>$stats['open'],        'label'=>'Open Incidents'],
            ['icon'=>'check-circle-fill',         'color'=>'success','value'=>$stats['resolved'],    'label'=>'Resolved'],
            ['icon'=>'exclamation-octagon-fill',  'color'=>'danger', 'value'=>$stats['high_critical'],'label'=>'High/Critical'],
            ['icon'=>'list-check',                'color'=>'info',   'value'=>$stats['total'],       'label'=>'Total Incidents'],
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
    @endif
</div>
@endsection

@push('scripts')
<script>
{{--
    Filter tabel insiden ditangani oleh class TableFilter dari charts.js.
    Fungsi filterTable() dan resetFilter() sudah didaftarkan ke window scope
    secara global di charts.js, sehingga tombol "Reset Filter" dan dropdown
    filter otomatis berfungsi tanpa script tambahan di sini.
--}}
</script>
@endpush
