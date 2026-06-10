{{-- resources/views/websites/index.blade.php --}}
{{-- Menggantikan: public/websites.php --}}
@extends('layouts.app')

@section('title', 'Daftar Website')

@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3">
        <i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong> — Halaman ini hanya dapat diakses oleh Administrator
    </div>

    <h3 class="mb-4">Daftar Website</h3>

    {{-- Header Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('websites.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Website
        </a>
        <div class="text-muted">Total: <strong>{{ $stats['total'] }}</strong> website</div>
    </div>

    {{-- Tabel Website --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Nama</th>
                            <th width="25%">URL</th>
                            <th width="10%">Status</th>
                            <th width="10%">Interval</th>
                            <th width="12%">Last Check</th>
                            <th width="10%">Response</th>
                            <th width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($websites as $i => $w)
                        @php
                            $rtClass = 'bg-success';
                            if (($w->response_time_ms ?? 0) > 500)  $rtClass = 'bg-warning text-dark';
                            if (($w->response_time_ms ?? 0) > 1000) $rtClass = 'bg-danger';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $w->name }}</strong>
                                @if($w->uptime_percentage)
                                    <br><small class="text-muted">Uptime: {{ $w->uptime_percentage }}%</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ $w->url }}" target="_blank" class="text-decoration-none">
                                    {{ $w->url }} <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $w->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                    <i class="bi bi-{{ $w->status === 'active' ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ ucfirst($w->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $w->check_interval_minutes }} min</span>
                            </td>
                            <td>
                                @if($w->last_checked)
                                    <small>{{ $w->last_checked->format('d/m H:i') }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                @if(($w->response_time_ms ?? 0) > 0)
                                    <span class="badge {{ $rtClass }}">{{ $w->response_time_ms }} ms</span>
                                @else
                                    <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('websites.edit', $w->website_id) }}" class="btn btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('websites.destroy', $w->website_id) }}" style="display:inline"
                                          onsubmit="return confirm('Hapus website \'{{ $w->name }}\'?\nSemua data terkait akan dihapus permanen.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada website. Silakan <a href="{{ route('websites.create') }}">tambah website baru</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    @if($websites->isNotEmpty())
    <div class="row mt-4">
        @foreach([
            ['color'=>'success','value'=>$stats['active'],       'label'=>'Website Aktif'],
            ['color'=>'danger', 'value'=>$stats['nonactive'],    'label'=>'Website Nonaktif'],
            ['color'=>'info',   'value'=>$stats['avg_response'].' ms','label'=>'Avg Response Time'],
            ['color'=>'primary','value'=>$stats['avg_uptime'].'%','label'=>'Avg Uptime'],
        ] as $card)
        <div class="col-md-3">
            <div class="card border-{{ $card['color'] }}">
                <div class="card-body text-center">
                    <h5 class="text-{{ $card['color'] }}">{{ $card['value'] }}</h5>
                    <p class="mb-0 text-muted">{{ $card['label'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
