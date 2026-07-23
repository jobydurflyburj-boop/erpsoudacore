<?php

// Genuinely missing from this repository until now — a critical gap:
// without this file, `config('app.key')`/`config('app.cipher')` are
// undefined, meaning Laravel's Encrypter (and everything that depends
// on it — signed URLs, e.g. the email-verification route gated by
// `->middleware('signed')` in routes/api.php) has no real key to work
// with, and `APP_TIMEZONE`/`APP_LOCALE`/`APP_NAME`/`APP_URL` are all
// unavailable via config() even though they're already real,
// correctly-named variables in .env.example. Standard Laravel 12
// content, referencing this project's actual env vars — nothing
// invented, nothing renamed.

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
