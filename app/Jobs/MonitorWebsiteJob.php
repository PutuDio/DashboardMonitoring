<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\Incident;
use App\Models\UptimeLog;
use App\Models\ContentSnapshot;
use App\Models\SslLog;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Menggantikan: workers/worker.php + workers/worker_daemon.php
 *
 * Di Laravel, background worker ditangani oleh:
 *   - Queue Jobs (file ini) → dijalankan oleh `php artisan queue:work`
 *   - Scheduler           → `php artisan schedule:run` (via cron 1 menit)
 *
 * Keunggulan vs daemon PHP manual:
 *   - Auto-restart jika error (dengan Supervisor)
 *   - Memory leak aman (setiap job fresh instance)
 *   - Retry otomatis jika gagal
 *   - Bisa monitor via Laravel Horizon
 */
class MonitorWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly Website $website)
    {
    }

    // ── Main job execution ────────────────────────────────────────
    public function handle(TelegramService $telegram): void
    {
        $website = $this->website;
        Log::info("[MonitorJob] Checking: {$website->name} | {$website->url}");

        // ── 1. Cek website ────────────────────────────────────────
        $result = $this->checkWebsite($website->url);

        // ── 2. Log uptime ─────────────────────────────────────────
        UptimeLog::create([
            'website_id'      => $website->website_id,
            'http_status'     => $result['http_code'],
            'response_time_ms'=> $result['response_time_ms'],
        ]);

        // Update last_checked
        $website->update(['last_checked' => now()]);

        // ── 3. Server Downtime ────────────────────────────────────
        if (!$result['success']) {
            Log::warning("[MonitorJob] DOWN: {$website->name} | HTTP {$result['http_code']}");
            $this->createIncidentIfNotExists($website, 'Server Downtime', 'High',
                "Website tidak dapat diakses. HTTP: {$result['http_code']}",
                [
                    'http_status'       => (string) $result['http_code'],
                    'response_message'  => $result['error'] ?: 'Service Unavailable',
                    'last_success_check'=> $this->getLastSuccessCheck($website->website_id),
                ],
                null, null, $telegram
            );
            return;
        }

        Log::info("[MonitorJob] UP: {$website->name} | {$result['response_time_ms']}ms");

        // ── 4. SSL Check ──────────────────────────────────────────
        if (str_starts_with($website->url, 'https://')) {
            $ssl = $this->checkSSL($website->url);

            if ($ssl) {
                // Catat log SSL berkala ke tabel ssl_logs (Bug #6 fix)
                SslLog::create([
                    'website_id'     => $website->website_id,
                    'ssl_valid'      => $ssl['valid'],
                    'ssl_issuer'     => $ssl['issuer'],
                    'ssl_expires_at' => $ssl['valid_to'],
                ]);

                $warningDays = config('monitoring.ssl_warning_days', 30);

                if (!$ssl['valid']) {
                    $this->createIncidentIfNotExists($website, 'SSL Certificate Expired', 'Critical',
                        "Sertifikat SSL telah kedaluwarsa.",
                        [
                            'ssl_expiry_date' => $ssl['valid_to'],
                            'days_expired'    => abs($ssl['expires_in_days']),
                            'ssl_issuer'      => $ssl['issuer'],
                        ],
                        null, null, $telegram
                    );
                } elseif ($ssl['expires_in_days'] > 0 && $ssl['expires_in_days'] <= $warningDays) {
                    $severity = $ssl['expires_in_days'] <= 7 ? 'High' : 'Medium';
                    $this->createIncidentIfNotExists($website, 'SSL Certificate Expired', $severity,
                        "Sertifikat SSL akan kedaluwarsa dalam {$ssl['expires_in_days']} hari.",
                        ['ssl_expiry_date' => $ssl['valid_to'], 'days_expired' => $ssl['expires_in_days'], 'ssl_issuer' => $ssl['issuer']],
                        null, null, $telegram
                    );
                }
            }
        }

        // ── 5. Slow Response ──────────────────────────────────────
        $threshold = config('monitoring.response_time_threshold', 3000);
        if ($result['response_time_ms'] > $threshold) {
            $severity = $result['response_time_ms'] > 5000 ? 'High' : 'Medium';
            $this->createIncidentIfNotExists($website, 'Slow Response', $severity,
                "Response time melebihi threshold ({$result['response_time_ms']}ms > {$threshold}ms)",
                [
                    'current_response_time' => $result['response_time_ms'] . ' ms',
                    'threshold'             => $threshold . ' ms',
                    'average_response_time' => $result['response_time_ms'] . ' ms',
                ],
                null, null, $telegram
            );
        }

        // ── 6. HTTP Error ─────────────────────────────────────────
        if ($result['http_code'] >= 400) {
            $severity = $result['http_code'] >= 500 ? 'High' : 'Medium';
            $this->createIncidentIfNotExists($website, "HTTP Error {$result['http_code']}", $severity,
                "Website mengembalikan HTTP {$result['http_code']}",
                [
                    'error_code' => (string) $result['http_code'],
                    'error_type' => $this->httpCodeToMessage($result['http_code']),
                    'solution'   => $this->httpCodeToSolution($result['http_code']),
                ],
                null, null, $telegram
            );
        }

        // ── 7. Content Change ─────────────────────────────────────
        if (!empty($result['content'])) {
            $this->checkContentChange($website, $result['content'], $telegram);
        }
    }

    // ────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────

    private function checkWebsite(string $url): array
    {
        $start = microtime(true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Website Monitor Bot)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $content  = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        $ms = (int) round((microtime(true) - $start) * 1000);

        return [
            'success'          => ($content !== false && empty($error) && $httpCode > 0),
            'http_code'        => $httpCode,
            'response_time_ms' => $ms,
            'content'          => $content ?: '',
            'error'            => $error,
        ];
    }

    private function checkSSL(string $url): ?array
    {
        try {
            $host    = parse_url($url, PHP_URL_HOST);
            $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
            $stream  = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
            if (!$stream) return null;

            $params  = stream_context_get_params($stream);
            fclose($stream);

            $cert    = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? null);
            if (!$cert) return null;

            $expiry  = $cert['validTo_time_t'];
            $now     = time();

            return [
                'valid'           => $expiry > $now,
                'expires_in_days' => (int) floor(($expiry - $now) / 86400),
                'issuer'          => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                'valid_to'        => date('Y-m-d H:i:s', $expiry),
            ];
        } catch (\Exception $e) {
            Log::error("SSL check failed: " . $e->getMessage());
            return null;
        }
    }

    private function checkContentChange(Website $website, string $currentHtml, TelegramService $telegram): void
    {
        // Skip jika ada Open Content Change incident
        $openExists = Incident::where('website_id', $website->website_id)
            ->where('type', 'Content Change')
            ->where('status', 'Open')
            ->exists();

        if ($openExists) return;

        $lastSnap = ContentSnapshot::where('website_id', $website->website_id)
            ->orderByDesc('created_at')
            ->first();

        $newHash = hash('sha256', $this->normalizeHtml($currentHtml));

        if (!$lastSnap) {
            ContentSnapshot::create(['website_id' => $website->website_id, 'html' => $currentHtml, 'content_hash' => $newHash]);
            return;
        }

        $oldHash = hash('sha256', $this->normalizeHtml($lastSnap->html));
        if ($newHash === $oldHash) return;

        // Content berubah!
        Log::warning("[MonitorJob] CONTENT CHANGE: {$website->name}");

        similar_text($lastSnap->html, $currentHtml, $similarity);

        $this->createIncidentIfNotExists($website, 'Content Change', 'High',
            "Terdeteksi perubahan konten. Kemiripan: " . round($similarity, 1) . "%",
            ['similarity' => round($similarity, 1)],
            $lastSnap->id,
            $currentHtml,
            $telegram
        );
    }

    private function createIncidentIfNotExists(
        Website $website,
        string $type,
        string $severity,
        string $description,
        array $metadata = [],
        ?int $snapshotBeforeId = null,
        ?string $snapshotAfter = null,
        ?TelegramService $telegram = null
    ): void {
        // Cegah duplikasi incident yang masih Open
        $exists = Incident::where('website_id', $website->website_id)
            ->where('type', $type)
            ->where('status', 'Open')
            ->exists();

        if ($exists) return;

        $incident = Incident::create([
            'website_id'        => $website->website_id,
            'type'              => $type,
            'severity'          => strtolower($severity),
            'status'            => 'Open',
            'description'       => $description,
            'metadata'          => $metadata,
            'snapshot_before_id'=> $snapshotBeforeId,
            'snapshot_after'    => $snapshotAfter,
        ]);

        Log::warning("[MonitorJob] Incident created: #{$incident->incident_id} | {$type} | {$website->name}");

        // Kirim Telegram notification
        if ($telegram) {
            $telegram->notifyAttack([
                'name'        => $website->name,
                'url'         => $website->url,
                'type'        => $type,
                'severity'    => $severity,
                'description' => $description,
                'created_at'  => now()->toDateTimeString(),
            ]);
        }
    }

    private function getLastSuccessCheck(int $websiteId): string
    {
        $log = UptimeLog::where('website_id', $websiteId)
            ->where('http_status', 200)
            ->orderByDesc('created_at')
            ->first();

        return $log ? $log->created_at->format('d M Y, H:i') . ' WIB' : 'Unknown';
    }

    private function normalizeHtml(string $html): string
    {
        // Hilangkan token, timestamp, session ID (sama dengan content_analyzer.php)
        $html = preg_replace('/<input[^>]*name=["\']?(csrf|nonce|token|_token)[^"\']*["\']?[^>]*>/i', '', $html);
        $html = preg_replace('/\b\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\b/i', '[DATETIME]', $html);
        $html = preg_replace('/([?&])(PHPSESSID|sessionid)=[a-zA-Z0-9]+/i', '$1', $html);
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    private function httpCodeToMessage(int $code): string
    {
        return [400=>'Bad Request',401=>'Unauthorized',403=>'Forbidden',404=>'Not Found',
                500=>'Internal Server Error',502=>'Bad Gateway',503=>'Service Unavailable',504=>'Gateway Timeout'][$code]
               ?? "HTTP Error {$code}";
    }

    private function httpCodeToSolution(int $code): string
    {
        return [400=>'Periksa format request',401=>'Periksa konfigurasi autentikasi',
                403=>'Periksa permission dan .htaccess',404=>'Periksa URL dan halaman',
                500=>'Periksa error log dan restore backup',503=>'Restart service dan cek resource'][$code]
               ?? 'Periksa log error server';
    }
}
