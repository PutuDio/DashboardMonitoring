<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Website;
use Illuminate\Http\Request;

/**
 * Menggantikan: report_control.php + report.php
 */
class ReportController extends Controller
{
    // ── Tampilkan laporan ─────────────────────────────────────────
    public function index(Request $request)
    {
        $filterType    = $request->input('filter_type', 'this_month');
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear  = $request->input('year', date('Y'));

        [$startDate, $endDate] = $this->calculateDateRange($filterType, $selectedMonth, $selectedYear);

        // Ambil insiden dalam range
        $incidents = Incident::with('website')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($i) {
                $i->name = $i->website->name ?? '-';
                $i->url  = $i->website->url  ?? '-';
                return $i;
            });

        $statistics  = $this->calculateStatistics($incidents);
        $chartData   = $this->generateChartData($incidents);
        $websiteStats = $this->getWebsiteStats();
        $periodDisplay = $this->formatPeriod($filterType, $startDate);

        $quickFilters = [
            'this_month' => ['icon' => 'calendar-month', 'label' => 'Bulan Ini',   'class' => 'primary'],
            'last_month' => ['icon' => 'calendar-month', 'label' => 'Bulan Lalu',  'class' => 'primary'],
            'this_year'  => ['icon' => 'calendar4-range','label' => 'Tahun Ini',   'class' => 'info'],
            'last_year'  => ['icon' => 'calendar4-range','label' => 'Tahun Lalu',  'class' => 'info'],
            'all_time'   => ['icon' => 'hourglass-end',  'label' => 'Semua Waktu', 'class' => 'secondary'],
        ];

        return view('reports.index', compact(
            'incidents', 'statistics', 'chartData', 'websiteStats',
            'startDate', 'endDate', 'filterType', 'selectedMonth', 'selectedYear',
            'periodDisplay', 'quickFilters'
        ));
    }

    // ── Export PDF ────────────────────────────────────────────────
    public function exportPdf(Request $request)
    {
        // Implementasikan dengan paket seperti barryvdh/laravel-dompdf
        // composer require barryvdh/laravel-dompdf
        //
        // $pdf = \PDF::loadView('reports.pdf', compact('incidents', ...));
        // return $pdf->download('laporan-monitoring.pdf');

        return redirect()->route('reports.index')
            ->with('info', 'Export PDF: install barryvdh/laravel-dompdf terlebih dahulu.');
    }

    // ── Export Excel ──────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        // Implementasikan dengan paket maatwebsite/excel
        // composer require maatwebsite/excel
        //
        // return Excel::download(new IncidentsExport($incidents), 'laporan.xlsx');

        return redirect()->route('reports.index')
            ->with('info', 'Export Excel: install maatwebsite/excel terlebih dahulu.');
    }

    // ────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────

    private function calculateDateRange(string $filterType, string $month, string $year): array
    {
        return match ($filterType) {
            'this_month' => [date('Y-m-01'), date('Y-m-t')],
            'last_month' => [
                date('Y-m-01', strtotime('first day of last month')),
                date('Y-m-t',  strtotime('first day of last month')),
            ],
            'this_year'  => [date('Y-01-01'), date('Y-12-31')],
            'last_year'  => [(date('Y') - 1) . '-01-01', (date('Y') - 1) . '-12-31'],
            'all_time'   => ['2020-01-01', date('Y-m-d')],
            'custom'     => [
                "{$year}-{$month}-01",
                date('Y-m-t', strtotime("{$year}-{$month}-01")),
            ],
            default      => [date('Y-m-01'), date('Y-m-t')],
        };
    }

    private function calculateStatistics($incidents): array
    {
        $resolved = $incidents->filter(fn($i) => strtolower($i->status) === 'resolved');

        $resolutionTimes = $resolved
            ->filter(fn($i) => $i->resolved_at)
            ->map(fn($i) => $i->resolved_at->diffInHours($i->created_at));

        return [
            'total'               => $incidents->count(),
            'open'                => $incidents->count() - $resolved->count(),
            'resolved'            => $resolved->count(),
            'avg_resolution_time' => $resolutionTimes->isNotEmpty()
                ? round($resolutionTimes->avg(), 1)
                : 0,
        ];
    }

    private function generateChartData($incidents): array
    {
        $byDate = [];
        foreach ($incidents as $i) {
            $date = $i->created_at->format('d M');
            $byDate[$date] = ($byDate[$date] ?? 0) + 1;
        }
        ksort($byDate);

        $severity = [];
        foreach ($incidents as $i) {
            $severity[$i->severity] = ($severity[$i->severity] ?? 0) + 1;
        }

        $type = [];
        foreach ($incidents as $i) {
            $type[$i->type] = ($type[$i->type] ?? 0) + 1;
        }
        arsort($type);

        return [
            'incidents_by_date' => $byDate,
            'severity_count'    => $severity,
            'type_count'        => $type,
        ];
    }

    private function getWebsiteStats(): array
    {
        $stats = Website::selectRaw("
            SUM(CASE WHEN LOWER(status)='active'    THEN 1 ELSE 0 END) AS active_sites,
            SUM(CASE WHEN LOWER(status)='nonactive' THEN 1 ELSE 0 END) AS nonactive_sites,
            COUNT(*) AS total_sites
        ")->first();

        return $stats->toArray();
    }

    private function formatPeriod(string $filterType, string $startDate): string
    {
        return match ($filterType) {
            'this_year', 'last_year' => date('Y', strtotime($startDate)),
            'all_time'               => 'Semua Waktu',
            default                  => date('F Y', strtotime($startDate)),
        };
    }

    // ── Static helper (dipakai di Blade) ─────────────────────────
    public static function calculateIncidentDuration(Incident $incident): array
    {
        $end  = $incident->resolved_at ?? now();
        $secs = $end->diffInSeconds($incident->created_at);

        return [
            'hours'         => floor($secs / 3600),
            'minutes'       => floor(($secs % 3600) / 60),
            'total_seconds' => $secs,
        ];
    }
}
