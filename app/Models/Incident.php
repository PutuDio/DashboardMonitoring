<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $primaryKey = 'incident_id';

    protected $fillable = [
        'website_id', 'type', 'severity', 'status',
        'description', 'metadata', 'snapshot_before_id',
        'snapshot_after', 'resolved_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'resolved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function scopeOpen($query)       { return $query->where('status', 'Open'); }
    public function scopeResolved($query)   { return $query->where('status', 'Resolved'); }
    public function scopeHighCritical($query) { return $query->whereIn('severity', ['high', 'critical']); }
    public function scopeInDateRange($query, string $start, string $end)
    {
        return $query->whereDate('created_at', '>=', $start)
                     ->whereDate('created_at', '<=', $end);
    }

    public function getDurationAttribute(): array
    {
        $end  = $this->resolved_at ?? now();
        $secs = $end->diffInSeconds($this->created_at);
        return [
            'hours'         => floor($secs / 3600),
            'minutes'       => floor(($secs % 3600) / 60),
            'total_seconds' => $secs,
        ];
    }

    public function getIsOpenAttribute(): bool
    {
        return strtolower($this->status) === 'open';
    }
}