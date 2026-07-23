<?php

return [
    'password_history_limit' => env('PASSWORD_HISTORY_LIMIT', 5),

    'failed_login' => [
        'max_attempts' => env('FAILED_LOGIN_MAX_ATTEMPTS', 5),
        'lockout_minutes' => env('FAILED_LOGIN_LOCKOUT_MINUTES', 15),
    ],

    'otp' => [
        'driver' => env('OTP_SMS_DRIVER', 'log'), // 'log' in dev — TODO(ops): 'unifonic' | 'msegat' in production
        'length' => env('OTP_LENGTH', 6),
        'ttl_minutes' => env('OTP_TTL_MINUTES', 5),
        'max_attempts' => 5,
    ],

    'tokens' => [
        'refresh_ttl_days' => env('REFRESH_TOKEN_TTL_DAYS', 30),
        'remember_me_ttl_days' => env('REMEMBER_ME_TTL_DAYS', 90),
    ],
];
