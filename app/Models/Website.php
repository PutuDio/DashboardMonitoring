<?php
// ============================================================
// FILE: app/Models/Website.php
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $primaryKey = 'website_id';

    protected $fillable = [
        'name',
        'url',
        'check_interval_minutes',
        'status',
        'last_checked',
    ];

    protected $casts = [
        'last_checked' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'website_id', 'website_id');
    }

    public function uptimeLogs(): HasMany
    {
        return $this->hasMany(UptimeLog::class, 'website_id', 'website_id');
    }

    public function contentSnapshots(): HasMany
    {
        return $this->hasMany(ContentSnapshot::class, 'website_id', 'website_id');
    }

    // ============================================
    // ACCESSORS (kalkulasi real-time)
    // ============================================

    /** Rata-rata response time 24 jam terakhir */
    public function getAvgResponseTimeAttribute(): int
    {
        return (int) $this->uptimeLogs()
            ->where('created_at', '>=', now()->subDay())
            ->avg('response_time_ms') ?? 0;
    }

    /** Uptime percentage 7 hari terakhir */
    public function getUptimePercentageAttribute(): float
    {
        $total = $this->uptimeLogs()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($total === 0) return 100.0;

        $up = $this->uptimeLogs()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('http_status', 200)
            ->count();

        return round(($up / $total) * 100, 1);
    }
}
