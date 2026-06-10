<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;

// ============================================
// AUTH ROUTES
// ============================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============================================
// AUTHENTICATED ROUTES
// ============================================
Route::middleware(['auth', 'session.security'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Incidents
    Route::middleware('can:view_incidents')->group(function () {
        Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{id}', [IncidentController::class, 'detail'])->name('incidents.detail');
    });

    Route::middleware('can:resolve_incidents')->group(function () {
        Route::post('/incidents/{id}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    });

    // Reports
    Route::middleware('can:view_reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');
    });

    // Settings (semua user yang login)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingController::class, 'changePassword'])->name('settings.password');

    // Admin Only Routes
    Route::middleware('role:admin')->group(function () {

        // Websites
        Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
        Route::get('/websites/create', [WebsiteController::class, 'create'])->name('websites.create');
        Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
        Route::get('/websites/{id}/edit', [WebsiteController::class, 'edit'])->name('websites.edit');
        Route::put('/websites/{id}', [WebsiteController::class, 'update'])->name('websites.update');
        Route::delete('/websites/{id}', [WebsiteController::class, 'destroy'])->name('websites.destroy');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
