<?php
// ============================================================
// FILE: config/services.php
// Tambahkan bagian 'telegram' ke file ini di proyek Laravel Anda
// ============================================================

return [

    // ... (biarkan konfigurasi lain yang sudah ada) ...

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── Tambahkan ini ─────────────────────────────────────────
    // Menggantikan: hardcode BOT_TOKEN & CHAT_IDS di lib/telegram.php
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_ids'  => env('TELEGRAM_CHAT_IDS', ''),   // Multiple ID: "-100xxx,-200yyy"
    ],

];


// ============================================================
// FILE: config/monitoring.php   (FILE BARU — buat di config/)
// Konfigurasi monitoring worker — menggantikan define() di worker.php
// ============================================================

// return [
//     'response_time_threshold' => env('MONITOR_RESPONSE_THRESHOLD', 3000), // ms
//     'check_ssl'               => env('MONITOR_CHECK_SSL', true),
//     'check_content'           => env('MONITOR_CHECK_CONTENT', true),
//     'timeout'                 => env('MONITOR_TIMEOUT', 30),
//     'daemon_sleep'            => env('MONITOR_DAEMON_SLEEP', 60),
// ];
