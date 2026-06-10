<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

/**
 * Menggantikan: dashboard_control.php + index.php (public/)
 */
class DashboardController extends Controller
{
    public function index()
    {
        // ── Statistik website ────────────────────────────────────
        $websiteStats = Website::selectRaw("
            SUM(CASE WHEN LOWER(status) = 'active'    THEN 1 ELSE 0 END) AS active_sites,
            SUM(CASE WHEN LOWER(status) = 'nonactive' THEN 1 ELSE 0 END) AS inactive_sites,
            COUNT(*) AS total_sites
        ")->first();

        // ── Statistik insiden ─────────────────────────────────────
        $incidentStats = Incident::selectRaw("
            COUNT(*) AS total_incidents,
            SUM(CASE WHEN LOWER(status) = 'open'     THEN 1 ELSE 0 END) AS open_incidents,
            SUM(CASE WHEN LOWER(status) = 'resolved' THEN 1 ELSE 0 END) AS resolved_incidents
        ")->first();

        $stats = array_merge(
            $websiteStats->toArray(),
            $incidentStats->toArray()
        );

        // ── 5 Website terbaru ─────────────────────────────────────
        $websites = Website::with([])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($w) {
                // Tambahkan kalkulasi uptime & response time
                $w->response_time_ms   = $w->avg_response_time;
                $w->uptime_percentage  = $w->uptime_percentage;
                return $w;
            });

        // ── 5 Insiden terbaru ─────────────────────────────────────
        $incidents = Incident::with('website')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($i) {
                $i->name = $i->website->name ?? '-';
                $i->url  = $i->website->url  ?? '-';
                return $i;
            });

        // ── Tren insiden 7 hari ───────────────────────────────────
        $trendRaw = DB::select("
            SELECT
                dates.date AS incident_date,
                COALESCE(COUNT(i.incident_id), 0) AS incident_count
            FROM (
                SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS date UNION ALL
                SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) UNION ALL
                SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) UNION ALL
                SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) UNION ALL
                SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) UNION ALL
                SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) UNION ALL
                SELECT CURDATE()
            ) dates
            LEFT JOIN incidents i ON DATE(i.created_at) = dates.date
            GROUP BY dates.date
            ORDER BY dates.date ASC
        ");

        $incidentTrend = [
            'labels' => collect($trendRaw)->map(fn($r) => date('D', strtotime($r->incident_date)))->toArray(),
            'data'   => collect($trendRaw)->map(fn($r) => (int) $r->incident_count)->toArray(),
        ];

        if (array_sum($incidentTrend['data']) === 0) {
            $incidentTrend = [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'data'   => [0, 0, 0, 0, 0, 0, 0],
            ];
        }

        // ── Response time data (24 jam) ───────────────────────────
        $rtRaw = DB::select("
            SELECT w.name, ul.created_at AS log_time, ul.response_time_ms
            FROM uptime_logs ul
            JOIN websites w ON ul.website_id = w.website_id
            WHERE LOWER(w.status) = 'active'
              AND ul.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY w.name, ul.created_at ASC
        ");

        $responseTimeData = [];
        foreach ($rtRaw as $row) {
            $responseTimeData[$row->name]['times'][]  = $row->log_time;
            $responseTimeData[$row->name]['values'][] = (int) $row->response_time_ms;
        }

        return view('dashboard.index', compact(
            'stats',
            'websites',
            'incidents',
            'incidentTrend',
            'responseTimeData'
        ));
    }
}
