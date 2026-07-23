<?php

// Genuinely missing from this repository until now. Real, standard
// Laravel 12 content — the 'users' provider pointing at the real
// App\Models\User (confirmed to exist and be the model every
// tenant-scoped and Super Admin identity in this project already
// uses) is what Sanctum's guard, the password-reset broker, and
// Laravel's own auth() helper all depend on being defined somewhere.

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Every real API request in this project authenticates via
        // Sanctum's bearer-token guard, not this 'web' session guard —
        // see app/Http/Middleware and routes/api.php's
        // `auth:sanctum` middleware, unaffected by this file (Sanctum
        // registers its own guard driver) but still relying on the
        // 'users' provider below to resolve `Auth::user()` to a real
        // App\Models\User.
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
