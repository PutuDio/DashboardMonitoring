<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        // Daftarkan semua permission agar @can dan middleware('can:...') berfungsi
        $permissions = [
            'manage_websites',
            'add_website',
            'edit_website',
            'delete_website',
            'add_user',
            'view_incidents',
            'resolve_incidents',
            'view_reports',
            'export_reports',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}