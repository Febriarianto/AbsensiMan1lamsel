<?php

$defaultTimeout = (int) env('UPDATE_HTTP_TIMEOUT', 20);

return [
    /*
    |--------------------------------------------------------------------------
    | Update Server Manifest URL
    |--------------------------------------------------------------------------
    |
    | URL manifest JSON yang Anda host sendiri. Contoh:
    | https://updates.example.com/absensindo/manifest.json
    |
    */
    'manifest_url' => (string) env('UPDATE_MANIFEST_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Update Public Key (PEM)
    |--------------------------------------------------------------------------
    |
    | Public key untuk verifikasi signature update (RSA). Bisa diisi langsung
    | format PEM atau base64 dari PEM.
    |
    */
    'public_key' => (string) env('UPDATE_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | License / Client Identifier (Opsional)
    |--------------------------------------------------------------------------
    */
    'license_key' => env('UPDATE_LICENSE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Hosts (Opsional)
    |--------------------------------------------------------------------------
    |
    | Jika diisi, ZIP update hanya boleh berasal dari host ini.
    | Contoh: ['updates.example.com'].
    |
    */
    'allowed_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('UPDATE_ALLOWED_HOSTS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => $defaultTimeout,

    /*
    |--------------------------------------------------------------------------
    | HTTP Connect Timeout
    |--------------------------------------------------------------------------
    |
    | Batas waktu untuk koneksi awal ke update server (detik).
    |
    */
    'connect_timeout' => (int) env('UPDATE_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Download Timeout & Retry
    |--------------------------------------------------------------------------
    |
    | Karena ukuran paket update bisa besar, timeout download sebaiknya lebih
    | lama dibanding manifest.
    |
    */
    'download_timeout' => (int) env('UPDATE_DOWNLOAD_TIMEOUT', max(60, $defaultTimeout)),
    'download_retry' => (int) env('UPDATE_DOWNLOAD_RETRY', 1),
    'download_retry_sleep_ms' => (int) env('UPDATE_DOWNLOAD_RETRY_SLEEP_MS', 750),

    'allow_insecure_http' => filter_var(env('UPDATE_ALLOW_INSECURE_HTTP', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths (Tidak akan ditimpa oleh updater)
    |--------------------------------------------------------------------------
    */
    'exclude' => [
        '.env',
        'storage/',
        'public/storage/',
        'bootstrap/cache/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-Update Environment Migration
    |--------------------------------------------------------------------------
    |
    | Opsional: sesuaikan .env lama setelah update selesai dipasang.
    | Mekanisme ini hanya berjalan bila nilai saat ini masih cocok dengan
    | "from", agar tidak menimpa konfigurasi customer yang sudah disesuaikan.
    |
    */
    'auto_migrate' => [
        'session_driver' => [
            'enabled' => filter_var(env('UPDATE_AUTO_MIGRATE_SESSION_DRIVER', true), FILTER_VALIDATE_BOOLEAN),
            'from' => (string) env('UPDATE_SESSION_DRIVER_FROM', 'database'),
            'to' => (string) env('UPDATE_SESSION_DRIVER_TO', 'file'),
        ],
        'cache_store' => [
            'enabled' => filter_var(env('UPDATE_AUTO_MIGRATE_CACHE_STORE', true), FILTER_VALIDATE_BOOLEAN),
            'from' => (string) env('UPDATE_CACHE_STORE_FROM', 'database'),
            'to' => (string) env('UPDATE_CACHE_STORE_TO', 'file'),
        ],
    ],
];
