<?php

use Laravel\Sanctum\Sanctum;

return [

    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        sprintf('%s%s', 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1', env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : '')
    )),

    'guard' => ['web'],

    // Access tokens issued at login expire — the refresh-token flow
    // (app/Services/TokenService.php) is what keeps a session alive
    // beyond this, by rotation, not by the access token itself living
    // forever. See docs/FOUNDATION.md "Authentication".
    'expiration' => (int) env('SANCTUM_ACCESS_TOKEN_TTL_MINUTES', 60),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
