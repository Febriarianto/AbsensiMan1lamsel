<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wa_gateway' => [
        // QUEUE: kirim WA via queue job (async, butuh queue worker)
        // REALTIME: kirim WA langsung tanpa queue setelah response API selesai
        // BEFORE: kirim WA sinkron (blocking)
        'dispatch_mode' => env('WA_NOTIFICATION_DISPATCH_MODE', 'QUEUE'),
        // Jeda acak antar pesan broadcast (ms).
        'broadcast_interval_min_ms' => (int) env('WA_BROADCAST_INTERVAL_MIN_MS', 5000),
        'broadcast_interval_max_ms' => (int) env('WA_BROADCAST_INTERVAL_MAX_MS', 10000),
    ],

    'telegram_bot' => [
        // Mengikuti pola dispatch WA agar channel eksternal tetap konsisten.
        'dispatch_mode' => env('TELEGRAM_NOTIFICATION_DISPATCH_MODE', env('WA_NOTIFICATION_DISPATCH_MODE', 'QUEUE')),
    ],

    'kemenag_sso' => [
        'url' => env('KEMENAG_SSO_URL', 'https://sso.kemenag.go.id/v2'),
        'client_id' => env('KEMENAG_SSO_CLIENT_ID'),
        'timeout' => (int) env('KEMENAG_SSO_TIMEOUT', 15),
    ],

    'simpeg_export' => [
        'url' => env('SIMPEG_EXPORT_API_URL', 'http://localhost:8000/api/v1/simpeg-employees'),
        'token' => env('SIMPEG_EXPORT_API_TOKEN'),
        'consumer_domain' => env('SIMPEG_EXPORT_CONSUMER_DOMAIN', 'localhost'),
        'per_page' => (int) env('SIMPEG_EXPORT_PER_PAGE', 100),
        'rate_limit' => (int) env('SIMPEG_EXPORT_RATE_LIMIT', 60),
        'timeout' => (int) env('SIMPEG_EXPORT_TIMEOUT', 30),
    ],

];
