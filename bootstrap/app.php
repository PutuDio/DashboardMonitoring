<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\MonitorWebsiteJob;
use App\Models\Website;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'session.security' => \App\Http\Middleware\SessionSecurity::class,
            'role'             => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withProviders([
        \App\Providers\AuthServiceProvider::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        // ── Dispatch monitoring job setiap menit ──────────────────────────
        // Laravel memeriksa website mana yang perlu dicek berdasarkan
        // check_interval_minutes (menggantikan daemon_control.sh)
        $schedule->call(function () {
            Website::where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('last_checked')
                      ->orWhereRaw('last_checked < DATE_SUB(NOW(), INTERVAL check_interval_minutes MINUTE)');
                })
                ->get()
                ->each(fn ($website) => MonitorWebsiteJob::dispatch($website));
        })->everyMinute()->name('dispatch-monitoring-jobs')->withoutOverlapping();

        // ── Bersihkan uptime_logs lebih dari 30 hari (setiap minggu) ──────
        $schedule->call(function () {
            \DB::table('uptime_logs')
               ->where('created_at', '<', now()->subDays(30))
               ->delete();
        })->weekly()->name('cleanup-old-logs');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();