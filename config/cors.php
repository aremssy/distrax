<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_ORIGINS in .env to a comma-separated list of allowed
    | origins. Defaults to the application URL; set * explicitly only for
    | development/open APIs. For production set explicit domains:
    | CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
    |
    | Mobile apps do not send an Origin header, so they are unaffected by
    | CORS policy regardless of these settings.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'X-Requested-With',
        'X-Idempotency-Key',
        'Accept-Language',
        'X-Currency',
    ],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
