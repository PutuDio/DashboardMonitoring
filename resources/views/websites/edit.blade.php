{{-- resources/views/websites/edit.blade.php --}}
{{-- Menggantikan: public/website_edit.php --}}
@extends('layouts.app')

@section('title', 'Edit Website')

@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3">
        <i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-pencil-square"></i> Edit Website</h3>
        <a href="{{ route('websites.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Info Website Saat Ini --}}
    <div class="card shadow-sm border-info mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Website Saat Ini</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><td width="150"><strong>ID Website</strong></td><td>: #{{ $website->website_id }}</td></tr>
                        <tr><td><strong>Status</strong></td>
                            <td>: <span class="badge {{ $website->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($website->status) }}</span></td></tr>
                        @if($website->created_at)
                        <tr><td><strong>Ditambahkan</strong></td><td>: {{ $website->created_at->format('d M Y, H:i') }} WIB</td></tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        @if($website->last_checked)
                        <tr><td width="150"><strong>Last Check</strong></td><td>: {{ $website->last_checked->format('d M Y, H:i') }} WIB</td></tr>
                        @endif
                        <tr><td><strong>Response Time</strong></td>
                            <td>: <span class="badge bg-primary">{{ $website->avg_response_time }} ms</span></td></tr>
                        <tr><td><strong>Uptime</strong></td>
                            <td>: <span class="badge bg-success">{{ $website->uptime_percentage }}%</span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Edit --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-pencil"></i> Form Edit Website</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('websites.update', $website->website_id) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-tag"></i> Nama Website <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $website->name) }}" required maxlength="200">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-link-45deg"></i> URL Website <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url', $website->url) }}" required>
                            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-clock-history"></i> Interval (menit) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="check_interval_minutes"
                                       class="form-control @error('check_interval_minutes') is-invalid @enderror"
                                       value="{{ old('check_interval_minutes', $website->check_interval_minutes) }}"
                                       min="1" max="1440" required>
                                <span class="input-group-text">menit</span>
                            </div>
                            @error('check_interval_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('[name=check_interval_minutes]').value=5">5 menit</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('[name=check_interval_minutes]').value=15">15 menit</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('[name=check_interval_minutes]').value=60">1 jam</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-toggle-on"></i> Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="active"    {{ old('status', $website->status) === 'active'    ? 'selected':'' }}>✅ Active - Monitoring aktif</option>
                                <option value="nonactive" {{ old('status', $website->status) === 'nonactive' ? 'selected':'' }}>⛔ Nonactive - Monitoring dinonaktifkan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('websites.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                    <a href="{{ $website->url }}" target="_blank" class="btn btn-info">
                        <i class="bi bi-box-arrow-up-right"></i> Buka Website
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4 border-warning">
        <div class="card-body">
            <h6 class="text-warning"><i class="bi bi-exclamation-triangle"></i> Perhatian</h6>
            <ul class="mb-0 small text-muted">
                <li>Perubahan URL akan mempengaruhi hasil monitoring</li>
                <li>Interval < 5 menit dapat membebani server</li>
                <li>Mengubah status ke "Nonactive" akan menghentikan monitoring</li>
            </ul>
        </div>
    </div>
</div>
@endsection
