{{-- resources/views/settings/index.blade.php --}}
{{-- Menggantikan: public/setting.php --}}
@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
<div class="container-fluid p-4">

    <h3 class="mb-4"><i class="bi bi-gear"></i> Pengaturan Akun</h3>

    <div class="row">

        {{-- ── Profil ─────────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> Informasi Profil</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.profile') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person-badge"></i> Username <span class="text-danger">*</span></label>
                            <input type="text" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   value="{{ old('username', $user->username) }}"
                                   required maxlength="50" pattern="[a-zA-Z0-9_]+"
                                   title="Hanya huruf, angka, underscore">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Username untuk login</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name"
                                   class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $user->full_name) }}"
                                   required maxlength="100">
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-shield-check"></i> Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <option value="admin"    {{ old('role', $user->role) === 'admin'    ? 'selected':'' }}>👑 Admin</option>
                                <option value="operator" {{ old('role', $user->role) === 'operator' ? 'selected':'' }}>👤 Operator</option>
                                <option value="viewer"   {{ old('role', $user->role) === 'viewer'   ? 'selected':'' }}>👁️ Viewer</option>
                            </select>
                            @if(!auth()->user()->isAdmin())
                                <input type="hidden" name="role" value="{{ $user->role }}">
                                <small class="text-warning"><i class="bi bi-lock"></i> Role hanya dapat diubah oleh Admin</small>
                            @endif
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <strong><i class="bi bi-info-circle"></i> Keterangan Role:</strong><br>
                                • <strong>Admin:</strong> Kelola website, user, insiden, laporan<br>
                                • <strong>Operator:</strong> Monitoring, insiden, laporan<br>
                                • <strong>Viewer:</strong> Hanya lihat dashboard dan laporan
                            </small>
                        </div>

                        @if($user->created_at)
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-calendar-plus"></i> Akun Dibuat</label>
                            <input type="text" class="form-control" value="{{ $user->created_at->format('d M Y, H:i') }} WIB" disabled>
                        </div>
                        @endif

                        @if($user->last_login)
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-clock-history"></i> Login Terakhir</label>
                            <input type="text" class="form-control" value="{{ $user->last_login->format('d M Y, H:i') }} WIB" disabled>
                        </div>
                        @endif

                        <div class="alert alert-warning">
                            <small><i class="bi bi-exclamation-triangle"></i>
                                Perubahan username akan mempengaruhi login Anda selanjutnya.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Password + Security ────────────────────────────────── --}}
        <div class="col-md-6">

            {{-- Ganti Password --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-key"></i> Ubah Password</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.password') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock"></i> Password Lama <span class="text-danger">*</span></label>
                            <input type="password" name="old_password"
                                   class="form-control @error('old_password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('old_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill"></i> Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="new_password"
                                   class="form-control @error('new_password') is-invalid @enderror"
                                   required minlength="6" autocomplete="new-password">
                            @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill"></i> Konfirmasi Password <span class="text-danger">*</span></label>
                            {{--
                                Laravel validation: 'confirmed' → field name harus new_password_confirmation
                            --}}
                            <input type="password" name="new_password_confirmation"
                                   class="form-control" required minlength="6" autocomplete="new-password">
                        </div>

                        <div class="alert alert-warning">
                            <small><i class="bi bi-exclamation-triangle"></i>
                                Setelah mengubah password, Anda tetap login di sesi ini.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-key"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>

            {{-- Security Info --}}
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> Keamanan Akun</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                        <div>
                            <strong>Session Aktif</strong>
                            <br><small class="text-muted">Sesi login Anda aman dan terenkripsi</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-shield-lock-fill text-success fs-3 me-3"></i>
                        <div>
                            <strong>Password Terenkripsi</strong>
                            <br><small class="text-muted">Menggunakan algoritma bcrypt</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-award-fill text-primary fs-3 me-3"></i>
                        <div>
                            <strong>Role Aktif</strong>
                            <br><span class="badge bg-primary">{{ strtoupper($user->role) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- System Info --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Sistem</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach([
                    ['icon'=>'hdd-network',          'color'=>'primary', 'value'=>$stats['total_websites'], 'label'=>'Total Website'],
                    ['icon'=>'exclamation-triangle',  'color'=>'warning', 'value'=>$stats['open_incidents'], 'label'=>'Open Incidents'],
                    ['icon'=>'people',                'color'=>'info',    'value'=>$stats['total_users'],    'label'=>'Total Users'],
                    ['icon'=>'server',                'color'=>'success', 'value'=>$stats['server_date'],    'label'=>'Server Date'],
                ] as $s)
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                        <i class="bi bi-{{ $s['icon'] }} text-{{ $s['color'] }} fs-1"></i>
                        <h4 class="mt-2 mb-0">{{ $s['value'] }}</h4>
                        <small class="text-muted">{{ $s['label'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
