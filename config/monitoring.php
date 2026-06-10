<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Response Time Threshold (ms)
    |--------------------------------------------------------------------------
    | Jika response time website melebihi nilai ini (milidetik), sistem akan
    | membuat insiden Slow Response. Dipakai di MonitorWebsiteJob.
    */
    'response_time_threshold' => (int) env('MONITOR_RESPONSE_THRESHOLD', 3000),

    /*
    |--------------------------------------------------------------------------
    | SSL Check
    |--------------------------------------------------------------------------
    | Aktifkan/matikan pemeriksaan SSL certificate untuk website https://.
    */
    'check_ssl' => (bool) env('MONITOR_CHECK_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Content Change Check
    |--------------------------------------------------------------------------
    | Aktifkan/matikan deteksi perubahan konten halaman website.
    */
    'check_content' => (bool) env('MONITOR_CHECK_CONTENT', true),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (detik)
    |--------------------------------------------------------------------------
    | Batas waktu tunggu saat mengecek website sebelum dianggap timeout/down.
    */
    'timeout' => (int) env('MONITOR_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | SSL Warning Days
    |--------------------------------------------------------------------------
    | Jika SSL akan kedaluwarsa dalam jumlah hari ini atau kurang,
    | sistem akan membuat insiden peringatan.
    */
    'ssl_warning_days' => (int) env('MONITOR_SSL_WARNING_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Log Retention Days
    |--------------------------------------------------------------------------
    | Uptime log lebih dari jumlah hari ini akan dibersihkan otomatis
    | oleh scheduled task mingguan.
    */
    'log_retention_days' => (int) env('MONITOR_LOG_RETENTION_DAYS', 30),

];
