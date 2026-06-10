{{-- resources/views/users/create.blade.php --}}
{{-- Menggantikan: public/user_add.php --}}
@extends('layouts.app')
@section('title', 'Tambah User')
@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3"><i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-person-plus"></i> Tambah User Baru</h3>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <div class="card shadow-sm border-info mb-4">
        <div class="card-body">
            <h6 class="text-info"><i class="bi bi-info-circle"></i> Keterangan Role</h6>
            <div class="row small">
                <div class="col-md-4"><strong>👑 Admin:</strong> Kelola website, user, insiden, laporan (Full Access)</div>
                <div class="col-md-4"><strong>👤 Operator:</strong> Monitoring & insiden, tidak bisa kelola website</div>
                <div class="col-md-4"><strong>👁️ Viewer:</strong> Read-only, hanya lihat dashboard & laporan</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Form Tambah User</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person-badge"></i> Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                                   placeholder="Contoh: johndoe" value="{{ old('username') }}"
                                   required maxlength="50" pattern="[a-zA-Z0-9_]+"
                                   title="Username hanya boleh huruf, angka, dan underscore">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Huruf, angka, underscore</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   placeholder="Contoh: John Doe" value="{{ old('full_name') }}" required maxlength="100">
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-shield-check"></i> Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin"    {{ old('role')==='admin'    ? 'selected':'' }}>👑 Admin</option>
                                <option value="operator" {{ old('role')==='operator' ? 'selected':'' }}>👤 Operator</option>
                                <option value="viewer"   {{ old('role')==='viewer'   ? 'selected':'' }}>👁️ Viewer</option>
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill"></i> Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill"></i> Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" placeholder="Ketik ulang password"
                                   required minlength="6" autocomplete="new-password">
                        </div>

                        <div id="passwordMatch" class="alert" style="display:none"></div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Simpan User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const pw = document.getElementById('password');
const pw2 = document.getElementById('password_confirmation');
const matchDiv = document.getElementById('passwordMatch');

function checkMatch() {
    if (!pw2.value) { matchDiv.style.display='none'; return; }
    matchDiv.style.display = 'block';
    if (pw.value === pw2.value) {
        matchDiv.className = 'alert alert-success';
        matchDiv.innerHTML = '<i class="bi bi-check-circle"></i> Password cocok!';
    } else {
        matchDiv.className = 'alert alert-danger';
        matchDiv.innerHTML = '<i class="bi bi-x-circle"></i> Password tidak cocok!';
    }
}
pw.addEventListener('input', checkMatch);
pw2.addEventListener('input', checkMatch);
</script>
@endpush
