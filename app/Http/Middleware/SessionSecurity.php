<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menggantikan validateSessionFingerprint() dan checkSessionViolation()
 * dari auth_middleware.php native PHP.
 *
 * Laravel sudah menangani:
 *   - CSRF  → VerifyCsrfToken middleware (otomatis)
 *   - Session cookie httponly/samesite → config/session.php
 *   - Session regenerate setelah login → AuthController
 *
 * Middleware ini menambahkan:
 *   - IP & User-Agent fingerprint check
 *   - Session timeout 30 menit (backup dari SESSION_LIFETIME)
 */
class SessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $session = $request->session();

        // ── 1. Fingerprint check (IP & User-Agent) ──────────────────
        $storedIp = $session->get('user_ip');
        $storedUa = $session->get('user_agent');

        if ($storedIp && $storedIp !== $request->ip()) {
            $this->terminateSession($request, 'IP mismatch');
            return redirect()->route('login')
                ->with('error', 'Session tidak valid. Silakan login kembali.');
        }

        if ($storedUa && $storedUa !== $request->userAgent()) {
            $this->terminateSession($request, 'UA mismatch');
            return redirect()->route('login')
                ->with('error', 'Session tidak valid. Silakan login kembali.');
        }

        // ── 2. Inactivity timeout 30 menit ──────────────────────────
        $lastActivity = $session->get('last_activity');
        if ($lastActivity && (time() - $lastActivity) > 1800) {
            $this->terminateSession($request, 'Timeout');
            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir karena tidak aktif.');
        }

        // ── 3. Update last_activity ──────────────────────────────────
        $session->put('last_activity', time());

        return $next($request);
    }

    private function terminateSession(Request $request, string $reason): void
    {
        $user = Auth::user();
        \Log::warning("[SessionSecurity] {$reason} | User: " . ($user?->username ?? 'unknown') . " | IP: " . $request->ip());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
