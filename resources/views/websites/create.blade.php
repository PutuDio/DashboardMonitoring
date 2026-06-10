{{-- resources/views/websites/create.blade.php --}}
{{-- Menggantikan: public/website_add.php --}}
@extends('layouts.app')

@section('title', 'Tambah Website')

@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3">
        <i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-plus-circle"></i> Tambah Website Baru</h3>
        <a href="{{ route('websites.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Info --}}
    <div class="card shadow-sm border-info mb-4">
        <div class="card-body">
            <h6 class="text-info"><i class="bi bi-info-circle"></i> Informasi</h6>
            <ul class="mb-0 small">
                <li>Pastikan URL website dapat diakses dan valid</li>
                <li>Sistem akan otomatis mengambil snapshot awal konten website</li>
                <li>Interval monitoring menentukan seberapa sering website dicek</li>
                <li>Status "Active" akan langsung memulai monitoring</li>
            </ul>
        </div>
    </div>

    {{-- Form --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Form Tambah Website</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('websites.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-tag"></i> Nama Website <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Contoh: Portal Resmi Pemkot Denpasar"
                                   value="{{ old('name') }}" required maxlength="200">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-link-45deg"></i> URL Website <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   placeholder="https://example.com"
                                   value="{{ old('url') }}" required>
                            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">URL lengkap termasuk https:// atau http://</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-clock-history"></i> Interval Monitoring <span class="text-danger">*</span></label>
                            <select name="check_interval_minutes" class="form-select @error('check_interval_minutes') is-invalid @enderror" required>
                                <option value="">-- Pilih Interval --</option>
                                <optgroup label="Interval Cepat">
                                    <option value="1"  {{ old('check_interval_minutes')=='1'  ? 'selected':'' }}>1 Menit (Real-time)</option>
                                    <option value="3"  {{ old('check_interval_minutes')=='3'  ? 'selected':'' }}>3 Menit</option>
                                    <option value="5"  {{ old('check_interval_minutes','5')=='5' ? 'selected':'' }}>5 Menit (Rekomendasi)</option>
                                </optgroup>
                                <optgroup label="Interval Normal">
                                    <option value="10" {{ old('check_interval_minutes')=='10' ? 'selected':'' }}>10 Menit</option>
                                    <option value="15" {{ old('check_interval_minutes')=='15' ? 'selected':'' }}>15 Menit</option>
                                    <option value="30" {{ old('check_interval_minutes')=='30' ? 'selected':'' }}>30 Menit</option>
                                </optgroup>
                                <optgroup label="Interval Panjang">
                                    <option value="60"  {{ old('check_interval_minutes')=='60'  ? 'selected':'' }}>1 Jam</option>
                                    <option value="120" {{ old('check_interval_minutes')=='120' ? 'selected':'' }}>2 Jam</option>
                                    <option value="360" {{ old('check_interval_minutes')=='360' ? 'selected':'' }}>6 Jam</option>
                                    <option value="720" {{ old('check_interval_minutes')=='720' ? 'selected':'' }}>12 Jam</option>
                                    <option value="1440"{{ old('check_interval_minutes')=='1440'? 'selected':'' }}>24 Jam</option>
                                </optgroup>
                            </select>
                            @error('check_interval_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-toggle-on"></i> Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active"    {{ old('status','active') === 'active'    ? 'selected':'' }}>✅ Active - Monitoring aktif</option>
                                <option value="nonactive" {{ old('status') === 'nonactive' ? 'selected':'' }}>⛔ Nonactive - Monitoring dinonaktifkan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Simpan Website
                    </button>
                    <a href="{{ route('websites.index') }}" class="btn btn-secondary btn-lg">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tips --}}
    <div class="card shadow-sm mt-4 border-success">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Tips Memilih Interval</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-success">⚡ 1-5 menit</h6>
                    <small>Website kritikal, portal utama — deteksi sangat cepat</small>
                </div>
                <div class="col-md-4">
                    <h6 class="text-primary">⏱️ 10-30 menit</h6>
                    <small>Website umum, portal informasi — seimbang</small>
                </div>
                <div class="col-md-4">
                    <h6 class="text-info">🕐 1+ jam</h6>
                    <small>Website non-kritikal, arsip — beban minimal</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
