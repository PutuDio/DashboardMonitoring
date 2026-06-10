<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'user_id';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'role',
        'last_login',
    ];

    // Sembunyikan dari serialisasi
    protected $hidden = ['password_hash'];

    protected $casts = [
        'last_login'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ============================================
    // Agar Laravel Auth bisa pakai kolom password_hash
    // ============================================
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ============================================
    // ROLE CHECKS (menggantikan isAdmin(), isOperator())
    // ============================================
    public function isAdmin(): bool
    {
        return strtolower($this->role) === 'admin';
    }

    public function isOperator(): bool
    {
        return strtolower($this->role) === 'operator';
    }

    public function isViewer(): bool
    {
        return strtolower($this->role) === 'viewer';
    }

    // ============================================
    // PERMISSION CHECK (menggantikan hasPermission())
    // ============================================
    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'admin' => [
                'manage_websites', 'add_website', 'edit_website', 'delete_website',
                'add_user', 'view_incidents', 'resolve_incidents',
                'view_reports', 'export_reports', 'manage_settings',
            ],
            'operator' => [
                'view_incidents', 'resolve_incidents',
                'view_reports', 'export_reports', 'manage_settings',
            ],
            'viewer' => [
                'view_reports',
            ],
        ];

        $role = strtolower($this->role);

        return isset($permissions[$role])
            && in_array($permission, $permissions[$role]);
    }
}
