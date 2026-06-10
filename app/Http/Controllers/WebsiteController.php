<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\ContentSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Menggantikan: website_control.php + websites.php + website_add.php + website_edit.php
 */
class WebsiteController extends Controller
{
    // ── Daftar website ───────────────────────────────────────────
    public function index()
    {
        $websites = Website::orderByDesc('created_at')->get()->map(function ($w) {
            $w->response_time_ms  = $w->avg_response_time;
            $w->uptime_percentage = $w->uptime_percentage;
            return $w;
        });

        $responses = $websites->pluck('response_time_ms')->filter();
        $uptimes   = $websites->pluck('uptime_percentage')->filter();

        $stats = [
            'total'        => $websites->count(),
            'active'       => $websites->where('status', 'active')->count(),
            'nonactive'    => $websites->where('status', 'nonactive')->count(),
            'avg_response' => $responses->isNotEmpty() ? round($responses->avg()) : 0,
            'avg_uptime'   => $uptimes->isNotEmpty()   ? number_format($uptimes->avg(), 1) : 0,
        ];

        return view('websites.index', compact('websites', 'stats'));
    }

    // ── Form tambah website ──────────────────────────────────────
    public function create()
    {
        return view('websites.create');
    }

    // ── Simpan website baru ──────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:200',
            'url'                    => 'required|url|regex:/^https?:\/\//i',
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
            'status'                 => 'required|in:active,nonactive',
        ], [
            'url.url'   => '❌ URL tidak valid! Pastikan format URL benar (contoh: https://example.com)',
            'url.regex' => '❌ URL harus dimulai dengan https:// atau http://',
        ]);

        $website = Website::create($validated);

        // Ambil snapshot awal (sama seperti takeInitialSnapshot() di native)
        $this->takeInitialSnapshot($website);

        \Log::info("[WebsiteController] Website added: {$website->name} | ID: {$website->website_id} | by: " . Auth::user()->username);

        return redirect()->route('websites.index')
            ->with('success', "✅ Website berhasil ditambahkan! ID: #{$website->website_id}");
    }

    // ── Form edit website ────────────────────────────────────────
    public function edit(int $id)
    {
        $website = Website::findOrFail($id);
        return view('websites.edit', compact('website'));
    }

    // ── Update website ───────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $website = Website::findOrFail($id);

        $validated = $request->validate([
            'name'                   => 'required|string|max:200',
            'url'                    => 'required|url|regex:/^https?:\/\//i',
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
            'status'                 => 'required|in:active,nonactive',
        ]);

        $old = $website->toArray();
        $website->update($validated);

        \Log::info("[WebsiteController] Website updated: {$website->name} | old=" . json_encode($old) . " | by: " . Auth::user()->username);

        return redirect()->route('websites.index')
            ->with('success', "✅ Website '{$website->name}' berhasil diperbarui!");
    }

    // ── Hapus website ─────────────────────────────────────────────
    public function destroy(int $id)
    {
        $website = Website::findOrFail($id);
        $name = $website->name;

        // Hapus relasi manual jika tidak pakai ON DELETE CASCADE
        DB::transaction(function () use ($website) {
            $website->incidents()->delete();
            $website->uptimeLogs()->delete();
            $website->contentSnapshots()->delete();
            $website->delete();
        });

        \Log::info("[WebsiteController] Website deleted: {$name} | ID: {$id} | by: " . Auth::user()->username);

        return redirect()->route('websites.index')
            ->with('success', "✅ Website '{$name}' berhasil dihapus!");
    }

    // ── Helper: Snapshot awal ─────────────────────────────────────
    private function takeInitialSnapshot(Website $website): void
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout'         => 10,
                    'user_agent'      => 'Mozilla/5.0 (Website Monitor Bot)',
                    'follow_location' => true,
                ],
            ]);

            $html = @file_get_contents($website->url, false, $context);

            if ($html !== false && $html !== '') {
                ContentSnapshot::create([
                    'website_id'   => $website->website_id,
                    'html'         => $html,
                    'content_hash' => hash('sha256', $html),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to take initial snapshot for website #{$website->website_id}: " . $e->getMessage());
        }
    }
}
