{{-- resources/views/users/edit.blade.php --}}
{{-- Menggantikan: public/user_edit.php --}}
@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3"><i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-pencil-square"></i> Edit User</h3>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    {{-- Info User Saat Ini --}}
    <div class="card shadow-sm border-info mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informasi User Saat Ini</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><td width="150"><strong>User ID</strong></td><td>: #{{ $user->user_id }}</td></tr>
                        <tr><td><strong>Username</strong></td><td>: {{ $user->username }}</td></tr>
                        <tr><td><strong>Role</strong></td>
                            <td>: <span class="badge {{ match(strtolower($user->role)) { 'admin'=>'bg-danger','operator'=>'bg-primary',default=>'bg-secondary' } }}">
                                {{ ucfirst($user->role) }}</span></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><td width="150"><strong>Dibuat</strong></td><td>: {{ $user->created_at->format('d M Y, H:i') }} WIB</td></tr>
                        <tr><td><strong>Login Terakhir</strong></td>
                            <td>: {{ $user->last_login ? $user->last_login->format('d M Y, H:i').' WIB' : 'Belum pernah login' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Edit --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-pencil"></i> Form Edit User</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user->user_id) }}">
                @csrf @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person-badge"></i> Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                                   value="{{ old('username', $user->username) }}"
                                   required maxlength="50" pattern="[a-zA-Z0-9_]+">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $user->full_name) }}" required maxlength="100">
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-shield-check"></i> Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="admin"    {{ old('role', $user->role) === 'admin'    ? 'selected':'' }}>👑 Admin</option>
                                <option value="operator" {{ old('role', $user->role) === 'operator' ? 'selected':'' }}>👤 Operator</option>
                                <option value="viewer"   {{ old('role', $user->role) === 'viewer'   ? 'selected':'' }}>👁️ Viewer</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill"></i> Password Baru (Opsional)</label>
                            <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror"
                                   placeholder="Kosongkan jika tidak ingin mengubah"
                                   minlength="6" autocomplete="new-password">
                            @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Minimal 6 karakter. Kosongkan jika tidak diganti.</small>
                        </div>

                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong><br>
                                • Perubahan username mempengaruhi login user<br>
                                • Perubahan role mempengaruhi hak akses<br>
                                • Password baru akan mengganti password lama
                            </small>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
