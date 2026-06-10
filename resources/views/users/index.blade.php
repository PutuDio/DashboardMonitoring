{{-- resources/views/users/index.blade.php --}}
{{-- Menggantikan: public/user_management.php --}}
@extends('layouts.app')

@section('title', 'Kelola User')

@section('content')
<div class="container-fluid p-4">

    <div class="alert alert-danger mb-3">
        <i class="bi bi-shield-lock"></i> <strong>Admin Only Area</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-people"></i> Kelola User</h3>
        <a href="{{ route('users.create') }}" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Tambah User Baru
        </a>
    </div>

    <div class="card shadow-sm border-info mb-4">
        <div class="card-body">
            <h6 class="text-info"><i class="bi bi-info-circle"></i> Informasi</h6>
            <ul class="mb-0 small">
                <li><strong>Admin:</strong> Full akses — kelola website, user, insiden, laporan</li>
                <li><strong>Operator:</strong> Monitoring & insiden — tidak bisa kelola website</li>
                <li><strong>Viewer:</strong> Read-only — hanya melihat dashboard dan laporan</li>
            </ul>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-list"></i> Daftar User</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Username</th>
                            <th width="25%">Nama Lengkap</th>
                            <th width="12%">Role</th>
                            <th width="15%">Dibuat</th>
                            <th width="15%">Login Terakhir</th>
                            <th width="13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $i => $user)
                        @php
                            $roleClass = match(strtolower($user->role)) { 'admin'=>'bg-danger','operator'=>'bg-primary', default=>'bg-secondary' };
                            $roleIcon  = match(strtolower($user->role)) { 'admin'=>'shield-fill-check','operator'=>'person-badge', default=>'eye' };
                            $isMe = ($user->user_id == auth()->id());
                        @endphp
                        <tr {{ $isMe ? 'class=table-info' : '' }}>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $user->username }}</strong>
                                @if($isMe)<br><small class="badge bg-info">Anda</small>@endif
                            </td>
                            <td>{{ $user->full_name }}</td>
                            <td>
                                <span class="badge {{ $roleClass }}">
                                    <i class="bi bi-{{ $roleIcon }}"></i> {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $user->created_at->format('d M Y') }}</small>
                                <br><small class="text-muted">{{ $user->created_at->format('H:i') }} WIB</small>
                            </td>
                            <td>
                                @if($user->last_login)
                                    <small>{{ $user->last_login->format('d M Y H:i') }} WIB</small>
                                @else
                                    <small class="text-muted">Belum pernah login</small>
                                @endif
                            </td>
                            <td>
                                @if($isMe)
                                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-gear"></i> Setting
                                    </a>
                                @else
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('users.edit', $user->user_id) }}" class="btn btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('users.destroy', $user->user_id) }}" style="display:inline"
                                              onsubmit="return confirm('Hapus user \'{{ $user->username }}\'?\nData akan dihapus permanen.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i> Tidak ada data user.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $roleCount = $users->countBy('role');
    @endphp
    <div class="row mt-4">
        @foreach([
            ['role'=>'Admin',      'count'=>$roleCount->get('admin',0),    'color'=>'danger', 'icon'=>'shield-fill-check'],
            ['role'=>'Operator',   'count'=>$roleCount->get('operator',0), 'color'=>'primary','icon'=>'person-badge'],
            ['role'=>'Viewer',     'count'=>$roleCount->get('viewer',0),   'color'=>'secondary','icon'=>'eye'],
            ['role'=>'Total User', 'count'=>$users->count(),               'color'=>'success','icon'=>'people'],
        ] as $s)
        <div class="col-md-3">
            <div class="card border-{{ $s['color'] }} shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-{{ $s['icon'] }} text-{{ $s['color'] }} fs-1"></i>
                    <h4 class="mt-2 text-{{ $s['color'] }}">{{ $s['count'] }}</h4>
                    <p class="mb-0 text-muted">{{ $s['role'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
