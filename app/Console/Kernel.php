<?php

namespace App\Console;

use App\Jobs\MonitorWebsiteJob;
use App\Models\Website;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Menggantikan: workers/daemon_control.sh + manage_worker.bat
 *
 * Di Laravel, penjadwalan diatur di sini.
 * Yang perlu ditambahkan ke cron server (SEKALI SAJA):
 *   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
 *
 * Untuk menjalankan queue worker (pengganti daemon PHP):
 *   php artisan queue:work --sleep=3 --tries=3 --max-time=3600
 *   (Jalankan dengan Supervisor di production)
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ── Dispatch monitoring job setiap menit ──────────────────
        // Laravel akan memeriksa website mana yang perlu dicek
        // berdasarkan check_interval_minutes (sama seperti daemon loop)
        $schedule->call(function () {
            $websites = Website::where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('last_checked')
                      ->orWhereRaw('last_checked < DATE_SUB(NOW(), INTERVAL check_interval_minutes MINUTE)');
                })
                ->get();

            foreach ($websites as $website) {
                MonitorWebsiteJob::dispatch($website);
            }
        })->everyMinute()->name('dispatch-monitoring-jobs')->withoutOverlapping();

        // ── Bersihkan log lama setiap minggu (opsional) ───────────
        $schedule->call(function () {
            \DB::table('uptime_logs')
               ->where('created_at', '<', now()->subDays(30))
               ->delete();
        })->weekly()->name('cleanup-old-logs');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
