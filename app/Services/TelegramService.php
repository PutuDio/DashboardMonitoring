<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menggantikan: lib/telegram.php (class NotifTelegram)
 *
 * Perbedaan utama dengan native:
 *   - Menggunakan Laravel HTTP Client (Http::post) → lebih bersih dari cURL manual
 *   - Bot token & chat IDs dibaca dari .env (bukan hardcode di file)
 *   - Mudah di-mock saat testing
 */
class TelegramService
{
    private string $botToken;
    private array  $chatIds;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatIds  = explode(',', config('services.telegram.chat_ids', ''));
        $this->apiUrl   = "https://api.telegram.org/bot{$this->botToken}";
    }

    // ── Kirim notifikasi serangan / insiden ───────────────────────
    public function notifyAttack(array $incident): bool
    {
        $message = $this->formatAttackMessage($incident);
        $sent    = 0;

        foreach ($this->chatIds as $chatId) {
            $chatId = trim($chatId);
            if (!$chatId) continue;

            if ($this->sendMessage($chatId, $message)) {
                $sent++;
            }
        }

        if ($sent > 0) {
            Log::info("✅ Telegram sent to {$sent} recipients | Incident: {$incident['type']} | Website: {$incident['name']}");
        } else {
            Log::error("❌ Telegram FAILED | Incident: {$incident['type']} | Website: {$incident['name']}");
        }

        return $sent > 0;
    }

    // ── Kirim satu pesan ke satu chat ID ─────────────────────────
    private function sendMessage(string $chatId, string $message): bool
    {
        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                Log::error("Telegram error: HTTP {$response->status()} - {$response->body()}");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Telegram send failed: " . $e->getMessage());
            return false;
        }
    }

    // ── Format pesan ──────────────────────────────────────────────
    private function formatAttackMessage(array $incident): string
    {
        $emoji = $this->getSeverityEmoji($incident['severity']);
        $time  = isset($incident['created_at'])
            ? \Carbon\Carbon::parse($incident['created_at'])->format('d M Y H:i:s')
            : now()->format('d M Y H:i:s');

        $msg  = "<b>🚨 SERANGAN TERDETEKSI!</b>\n\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "<b>📍 Website:</b> {$incident['name']}\n";
        $msg .= "<b>🔗 URL:</b> {$incident['url']}\n";
        $msg .= "<b>⚠️ Jenis Serangan:</b> {$incident['type']}\n";
        $msg .= "<b>🔥 Tingkat Bahaya:</b> {$emoji} {$incident['severity']}\n";
        $msg .= "<b>🕐 Waktu:</b> {$time} WIB\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

        if (!empty($incident['description'])) {
            $msg .= "\n<b>📝 Detail:</b>\n{$incident['description']}\n\n";
        }

        $msg .= "⚡ <i>Segera lakukan penanganan!</i>\n";
        $msg .= "📊 <i>Sistem Monitoring Diskominfo Denpasar</i>";

        return $msg;
    }

    private function getSeverityEmoji(string $severity): string
    {
        return match (strtolower($severity)) {
            'low'      => '🟢',
            'medium'   => '🟡',
            'high'     => '🟠',
            'critical' => '🔴',
            default    => '⚪',
        };
    }
}
