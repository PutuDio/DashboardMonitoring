<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menggantikan requireAdmin() dari auth_middleware.php.
 * Dipakai via Route::middleware('role:admin')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user || strtolower($user->role) !== strtolower($role)) {
            \Log::warning("[CheckRole] Unauthorized: user {$user?->username} tried to access {$request->path()} (requires {$role})");

            return redirect()->route('dashboard')
                ->with('error', '⛔ Akses ditolak. Halaman ini hanya untuk ' . ucfirst($role) . '.');
        }

        return $next($request);
    }
}

