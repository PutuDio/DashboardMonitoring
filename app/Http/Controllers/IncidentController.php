<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Website;
use App\Models\ContentSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Menggantikan: incident_control.php + incident.php + incident_detail.php + incident_resolve.php
 */
class IncidentController extends Controller
{
    private const SEVERITY_CONFIG = [
        'low'      => ['class' => 'bg-success',          'icon' => 'check-circle'],
        'medium'   => ['class' => 'bg-warning text-dark', 'icon' => 'exclamation-triangle'],
        'high'     => ['class' => 'bg-danger',            'icon' => 'exclamation-circle'],
        'critical' => ['class' => 'bg-dark',              'icon' => 'exclamation-octagon'],
    ];

    // ── Daftar insiden ───────────────────────────────────────────
    public function index()
    {
        $incidents = Incident::with('website')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($i) => $this->appendWebsiteInfo($i));

        $stats = [
            'total'        => $incidents->count(),
            'open'         => $incidents->filter(fn($i) => strtolower($i->status) === 'open')->count(),
            'resolved'     => $incidents->filter(fn($i) => strtolower($i->status) === 'resolved')->count(),
            'high_critical'=> $incidents->filter(fn($i) => in_array(strtolower($i->severity), ['high', 'critical']))->count(),
        ];

        return view('incidents.index', compact('incidents', 'stats'));
    }

    // ── Detail insiden ───────────────────────────────────────────
    public function detail(int $id)
    {
        $incident = Incident::with('website')->findOrFail($id);
        $this->appendWebsiteInfo($incident);

        $details  = $this->processIncidentDetails($incident);
        $severity = self::SEVERITY_CONFIG[strtolower($incident->severity)] ?? ['class' => 'bg-secondary', 'icon' => 'info-circle'];
        $status   = $this->getStatusInfo($incident->status);
        $duration = $this->calculateDuration($incident);

        return view('incidents.detail', compact('incident', 'details', 'severity', 'status', 'duration'));
    }

    // ── Resolve insiden ──────────────────────────────────────────
    public function resolve(Request $request, int $id)
    {
        $incident = Incident::with('website')->findOrFail($id);

        if (strtolower($incident->status) === 'resolved') {
            return redirect()->route('incidents.detail', $id)
                ->with('error', '⚠️ Incident sudah resolved sebelumnya.');
        }

        // Kalau Content Change, perbarui snapshot
        if (stripos($incident->type, 'content') !== false) {
            $this->updateSnapshotAfterResolve($incident);
        }

        $incident->update([
            'status'      => 'Resolved',
            'resolved_at' => now(),
        ]);

        \Log::info("[IncidentController] Incident #{$id} resolved by " . Auth::user()->username);

        return redirect()->route('incidents.detail', $id)
            ->with('success', '✅ Incident berhasil di-resolve!');
    }

    // ────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────

    private function appendWebsiteInfo(Incident $incident): Incident
    {
        $incident->name = $incident->website->name ?? '-';
        $incident->url  = $incident->website->url  ?? '-';
        return $incident;
    }

    private function getStatusInfo(string $status): array
    {
        return strtolower($status) === 'open'
            ? ['class' => 'bg-warning text-dark', 'icon' => 'exclamation-circle', 'text' => 'Open']
            : ['class' => 'bg-success',            'icon' => 'check-circle',       'text' => 'Resolved'];
    }

    private function calculateDuration(Incident $incident): array
    {
        $end  = $incident->resolved_at ?? now();
        $secs = $end->diffInSeconds($incident->created_at);

        $hours   = floor($secs / 3600);
        $minutes = floor(($secs % 3600) / 60);

        $badgeClass = $secs < 3600 ? 'bg-success' : ($secs < 86400 ? 'bg-warning text-dark' : 'bg-danger');

        return [
            'hours'        => $hours,
            'minutes'      => $minutes,
            'text'         => "{$hours}h {$minutes}m",
            'badge_class'  => $badgeClass,
            'status'       => $incident->resolved_at ? 'resolved' : 'ongoing',
            'total_seconds'=> $secs,
        ];
    }

    private function processIncidentDetails(Incident $incident): array
    {
        $details = [];

        // Content Change (ada snapshot)
        if ($incident->snapshot_before_id || $incident->snapshot_after) {
            return $this->getContentChangeDetails($incident);
        }

        // Parse metadata
        if ($incident->metadata) {
            $details = is_array($incident->metadata) ? $incident->metadata : json_decode($incident->metadata, true) ?? [];
        }

        // Deteksi tipe dari nama incident
        $type = strtolower($incident->type);

        if (str_contains($type, 'ssl')) {
            $details['type'] = 'ssl_expired';
            $details['ssl_expiry_date'] ??= $details['expiry_date'] ?? 'Unknown';
            $details['days_expired']    ??= $details['days_until_expiry'] ?? 0;
            $details['ssl_issuer']      ??= $details['issuer'] ?? "Let's Encrypt Authority X3";

        } elseif (str_contains($type, 'downtime') || str_contains($type, 'down')) {
            $details['type'] = 'server_downtime';
            $details['http_status']       ??= $details['status_code'] ?? '503';
            $details['response_message']  ??= $details['error_message'] ?? 'Service Unavailable';
            if (!isset($details['downtime_duration'])) {
                $end   = $incident->resolved_at ?? now();
                $secs  = $end->diffInSeconds($incident->created_at);
                $h = floor($secs / 3600); $m = floor(($secs % 3600) / 60);
                $details['downtime_duration'] = "{$h} jam {$m} menit";
            }
            $details['last_success_check'] ??= $incident->website->last_checked?->format('d M Y, H:i') . ' WIB' ?? 'Unknown';

        } elseif (str_contains($type, 'slow')) {
            $details['type'] = 'slow_response';
            $details['current_response_time'] ??= $details['response_time'] ?? '3250 ms';
            $details['threshold']             ??= '1000 ms';
            $details['average_response_time'] ??= $details['avg_response_time'] ?? '410 ms';
            $details['peak_time']             ??= $incident->created_at->format('d M Y, H:i') . ' WIB';
            $details['chart_labels'] = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $details['chart_data']   = [450, 520, 380, 650, 890, 1200, 3250];

        } elseif (str_contains($type, 'http') || preg_match('/\d{3}/', $type)) {
            $details['type'] = 'http_error';
            preg_match('/\d{3}/', $type, $m);
            $details['error_code']    ??= $m[0] ?? '500';
            $details['error_type']    ??= $details['error_message'] ?? 'Internal Server Error';
            $details['affected_page'] ??= $details['page'] ?? '/';
            $details['first_detected']??= $incident->created_at->format('d M Y, H:i') . ' WIB';
            $details['stack_trace']   ??= $details['trace'] ?? 'Unknown';
            $details['solution']      ??= 'Restore file dari backup dan update dependencies';
        }

        return $details;
    }

    private function getContentChangeDetails(Incident $incident): array
    {
        $before = null;
        if ($incident->snapshot_before_id) {
            $snap   = ContentSnapshot::find($incident->snapshot_before_id);
            $before = $snap?->html;
        }
        $after = $incident->snapshot_after;

        return [
            'type'            => 'content_change',
            'snapshot_before' => $before ?? 'Tidak tersedia',
            'snapshot_after'  => $after  ?? 'Tidak tersedia',
            'alerts'          => [],
        ];
    }

    private function updateSnapshotAfterResolve(Incident $incident): void
    {
        try {
            $ch = curl_init($incident->website->url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Website Monitor Bot)',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $html     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($html && $httpCode === 200) {
                ContentSnapshot::create([
                    'website_id'   => $incident->website_id,
                    'html'         => $html,
                    'content_hash' => hash('sha256', $html),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("updateSnapshotAfterResolve failed: " . $e->getMessage());
        }
    }

    // ── Static helper (dipakai di Blade) ─────────────────────────
    public static function extractChangedSections(string $before, string $after, int $context = 3): array
    {
        $beforeLines = explode("\n", $before);
        $afterLines  = explode("\n", $after);
        $changedLines = [];

        $maxLines = max(count($beforeLines), count($afterLines));
        for ($i = 0; $i < $maxLines; $i++) {
            if (($beforeLines[$i] ?? '') !== ($afterLines[$i] ?? '')) {
                $changedLines[] = $i;
            }
        }

        if (empty($changedLines)) {
            return ['changes_count' => 0, 'before_sections' => [], 'after_sections' => []];
        }

        // Buat sections dengan konteks
        $beforeSections = [['start' => 1, 'end' => count($beforeLines), 'lines' => []]];
        foreach ($beforeLines as $idx => $line) {
            $lineNum = $idx + 1;
            $beforeSections[0]['lines'][] = [
                'number'  => $lineNum,
                'content' => $line,
                'changed' => in_array($idx, $changedLines),
            ];
        }

        $afterSections = [];
        foreach ($changedLines as $lineNum) {
            $start = max(0, $lineNum - $context);
            $end   = min(count($afterLines) - 1, $lineNum + $context);
            $lines = [];
            for ($i = $start; $i <= $end; $i++) {
                $lines[] = [
                    'number'  => $i + 1,
                    'content' => $afterLines[$i] ?? '',
                    'changed' => $i === $lineNum,
                ];
            }
            $afterSections[] = ['start' => $start + 1, 'end' => $end + 1, 'lines' => $lines];
        }

        return [
            'changes_count'   => count($changedLines),
            'before_sections' => $beforeSections,
            'after_sections'  => $afterSections,
        ];
    }
}
