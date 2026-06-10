<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Menggantikan: login.php, logout.php
 *
 * Laravel menangani otomatis:
 *   - CSRF token pada form (via @csrf di Blade)
 *   - Session regenerate setelah login
 *   - Password verify (password_hash → bcrypt compatible)
 */
class AuthController extends Controller
{
    // ── Tampilkan halaman login ──────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    // ── Proses login ─────────────────────────────────────────────
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        // Rate limiting (5 percobaan per 15 menit) — menggantikan logika manual login.php
        $throttleKey = 'login.' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'username' => "🚫 Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        // Coba login — Laravel otomatis pakai getAuthPassword() dari model
        if (!Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            RateLimiter::hit($throttleKey, 900); // window 15 menit

            $remaining = 5 - RateLimiter::attempts($throttleKey);
            throw ValidationException::withMessages([
                'username' => "❌ Username atau password salah! Sisa percobaan: {$remaining}",
            ]);
        }

        // Login berhasil
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();

        // Simpan fingerprint session (menggantikan $_SESSION['user_ip'] dst.)
        $request->session()->put('user_ip', $request->ip());
        $request->session()->put('user_agent', $request->userAgent());
        $request->session()->put('last_activity', time());

        // Update last_login di database
        $user->update(['last_login' => now()]);

        \Log::info("User login: {$user->username} | IP: {$request->ip()}");

        return redirect()->intended(route('dashboard'));
    }

    // ── Logout ───────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $user = Auth::user();
        \Log::info("User logout: {$user?->username} | IP: {$request->ip()}");

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', '✅ Anda telah berhasil logout.');
    }
}
