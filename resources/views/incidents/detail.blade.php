{{-- resources/views/incidents/detail.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Insiden')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-file-text"></i> Detail Insiden</h3>
        <a href="{{ route('incidents.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Info Utama --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Insiden</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong><i class="bi bi-globe"></i> Website</strong></td>
                            <td>: {{ $incident->name }}</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-link-45deg"></i> URL</strong></td>
                            <td>: <a href="{{ $incident->url }}" target="_blank">{{ $incident->url }} <i class="bi bi-box-arrow-up-right"></i></a></td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-tag"></i> Jenis</strong></td>
                            <td>: <span class="badge bg-info text-dark">{{ $incident->type }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Severity</strong></td>
                            <td>: <span class="badge {{ $severity['class'] }}">{{ $incident->severity }}</span></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong>Status</strong></td>
                            <td>: <span class="badge {{ $status['class'] }}">{{ $incident->status }}</span></td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-calendar-event"></i> Terdeteksi</strong></td>
                            <td>: {{ $incident->created_at->format('d M Y, H:i') }} WIB</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-calendar-check"></i> Resolved</strong></td>
                            <td>:
                                @if($incident->resolved_at)
                                    <span class="text-success">{{ $incident->resolved_at->format('d M Y, H:i') }} WIB</span>
                                @else
                                    <span class="text-muted">Belum resolved</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-clock-history"></i> Durasi</strong></td>
                            <td>:
                                <span class="badge {{ $duration['badge_class'] }}">
                                    {{ $duration['text'] }}
                                    @if($duration['status'] === 'ongoing') (ongoing) @endif
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($incident->description)
            <hr>
            <div class="alert alert-info mb-0">
                <strong><i class="bi bi-file-text"></i> Deskripsi:</strong><br>
                {!! nl2br(e($incident->description)) !!}
            </div>
            @endif
        </div>
    </div>

    @php $detailType = (!empty($details) && isset($details['type'])) ? $details['type'] : ''; @endphp

    @if(!empty($details))

        @if($detailType === 'content_change')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-file-diff"></i> Perbandingan Konten (Before vs After)</h5>
                </div>
                <div class="card-body">
                    @php
                        $before = isset($details['snapshot_before']) ? $details['snapshot_before'] : 'Tidak tersedia';
                        $after  = isset($details['snapshot_after'])  ? $details['snapshot_after']  : 'Tidak tersedia';
                    @endphp
                    @if($before !== 'Tidak tersedia' && $after !== 'Tidak tersedia')
                        @php
                            $comparison = \App\Http\Controllers\IncidentController::extractChangedSections($before, $after, 3);
                        @endphp
                        @if($comparison['changes_count'] > 0)
                            <div class="alert alert-warning">
                                <strong><i class="bi bi-exclamation-triangle"></i> Terdeteksi {{ $comparison['changes_count'] }} perubahan baris</strong>
                            </div>
                            @if(!empty($details['alerts']))
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-shield-exclamation"></i> Security Alerts!</h6>
                                <ul class="mb-0">
                                    @foreach($details['alerts'] as $alert)
                                        <li>{{ $alert }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="bi bi-shield-check"></i> BEFORE (Normal)</h6>
                                    <div class="bg-light p-2 rounded" style="max-height:400px;overflow:auto;font-size:11px;font-family:monospace;white-space:pre-wrap;">
@foreach($comparison['before_sections'] as $section)
@foreach($section['lines'] as $line)
@if($line['changed'])
<div class="bg-warning text-dark px-1">{{ $line['number'] }} | {{ $line['content'] }}</div>
@else
<div class="text-muted">{{ $line['number'] }} | {{ $line['content'] }}</div>
@endif
@endforeach
@endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-danger"><i class="bi bi-shield-exclamation"></i> AFTER (Serangan)</h6>
                                    <div class="bg-light p-2 rounded" style="max-height:400px;overflow:auto;font-size:11px;font-family:monospace;white-space:pre-wrap;">
@foreach($comparison['after_sections'] as $section)
<div class="text-secondary fst-italic mb-1">--- Lines {{ $section['start'] }} to {{ $section['end'] }} ---</div>
@foreach($section['lines'] as $line)
@if($line['changed'])
<div class="bg-danger text-white px-1">{{ $line['number'] }} | {{ $line['content'] }}</div>
@else
<div class="text-muted">{{ $line['number'] }} | {{ $line['content'] }}</div>
@endif
@endforeach
<hr class="my-1">
@endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">Tidak ada perubahan visual yang signifikan.</div>
                        @endif
                    @else
                        <div class="alert alert-secondary"><i class="bi bi-x-circle"></i> Snapshot tidak tersedia.</div>
                    @endif
                </div>
            </div>

        @elseif($detailType === 'ssl_expired')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Detail SSL Certificate</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <td width="200"><strong>Tanggal Expired</strong></td>
                            <td>{{ isset($details['ssl_expiry_date']) ? $details['ssl_expiry_date'] : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Hari Expired</strong></td>
                            <td>{{ isset($details['days_expired']) ? $details['days_expired'] : '-' }} hari</td>
                        </tr>
                        <tr>
                            <td><strong>Issuer</strong></td>
                            <td>{{ isset($details['ssl_issuer']) ? $details['ssl_issuer'] : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

        @elseif($detailType === 'slow_response')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Detail Slow Response</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Response Time:</strong>
                            {{ isset($details['current_response_time']) ? $details['current_response_time'] : '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Threshold:</strong>
                            {{ isset($details['threshold']) ? $details['threshold'] : '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Rata-rata:</strong>
                            {{ isset($details['average_response_time']) ? $details['average_response_time'] : '-' }}
                        </div>
                    </div>
                    <canvas id="responseTimeChart" height="100"></canvas>
                </div>
            </div>

        @elseif($detailType === 'http_error')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-octagon"></i> Detail HTTP Error</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <td width="200"><strong>Error Code</strong></td>
                            <td><span class="badge bg-danger fs-6">{{ isset($details['error_code']) ? $details['error_code'] : '-' }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Error Type</strong></td>
                            <td>{{ isset($details['error_type']) ? $details['error_type'] : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Halaman</strong></td>
                            <td><code>{{ isset($details['affected_page']) ? $details['affected_page'] : '/' }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Solusi</strong></td>
                            <td>{{ isset($details['solution']) ? $details['solution'] : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

        @else
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detail Tambahan</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded">{{ json_encode($details, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif

    @endif

    {{-- Timeline --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Timeline Insiden</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker bg-danger"></div>
                    <div class="timeline-content">
                        <h6 class="mb-1 text-danger"><i class="bi bi-exclamation-triangle"></i> Insiden Terdeteksi</h6>
                        <small class="text-muted">{{ $incident->created_at->format('d M Y, H:i') }} WIB</small>
                        <p class="mb-0 mt-2">
                            <strong>{{ $incident->type }}</strong> —
                            Severity: <span class="badge {{ $severity['class'] }}">{{ $incident->severity }}</span>
                        </p>
                    </div>
                </div>
                @if($incident->resolved_at)
                <div class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                        <h6 class="mb-1 text-success"><i class="bi bi-check-circle-fill"></i> Insiden Resolved</h6>
                        <small class="text-muted">{{ $incident->resolved_at->format('d M Y, H:i') }} WIB</small>
                        <p class="mb-0 mt-2">Masalah telah diselesaikan.</p>
                    </div>
                </div>
                @else
                <div class="timeline-item">
                    <div class="timeline-marker bg-warning"></div>
                    <div class="timeline-content">
                        <h6 class="mb-1 text-warning"><i class="bi bi-hourglass-split"></i> Status: Ongoing</h6>
                        <p class="mb-0 mt-2">Masih dalam penanganan tim teknis.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Aksi --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-tools"></i> Aksi Tindak Lanjut</h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $incident->url }}" target="_blank" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right"></i> Buka Website
                </a>
                @if(strtolower($incident->status) === 'open')
                    @can('resolve_incidents')
                    <form method="POST" action="{{ route('incidents.resolve', $incident->incident_id) }}">
                        @csrf
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('Tandai incident ini sebagai resolved?')">
                            <i class="bi bi-check-circle"></i> Mark as Resolved
                        </button>
                    </form>
                    @endcan
                @endif
                <a href="{{ route('reports.index') }}" class="btn btn-info">
                    <i class="bi bi-file-earmark-bar-graph"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $isSlowResponse = (!empty($details) && isset($details['type']) && $details['type'] === 'slow_response');
    $chartLabels    = ($isSlowResponse && isset($details['chart_labels'])) ? $details['chart_labels'] : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $chartData      = ($isSlowResponse && isset($details['chart_data']))   ? $details['chart_data']   : [0,0,0,0,0,0,0];
    $thresholdRaw   = ($isSlowResponse && isset($details['threshold']))    ? $details['threshold']    : '1000';
    $thresholdVal   = (int) str_replace(['ms', ' ms'], '', $thresholdRaw);
@endphp
@if($isSlowResponse)
<script>
const incidentCharts = new IncidentDetailCharts();
incidentCharts.initResponseChart({
    labels:    {!! json_encode($chartLabels) !!},
    data:      {!! json_encode($chartData) !!},
    threshold: {{ $thresholdVal }}
});
</script>
@endif
@endpush
