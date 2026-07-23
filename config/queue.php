<?php

// Final Production Validation pass: this file was missing entirely —
// the application ran on Laravel's internal framework default (never
// materialized as a file in this repo) despite QUEUE_CONNECTION=redis
// being set in .env.example since Foundation, and despite
// docker-compose.yml running a real `queue:work` worker since
// Foundation. Publishing this explicitly, rather than continuing to
// rely on an unpublished framework default, makes the real queue
// connection map (and the `failed_jobs` table this project added in
// the Production Readiness sprint) visible and auditable in the
// repository itself.

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    // The real recovery path for a job that exhausts its retries —
    // `failed_jobs` (migration 2026_03_01_000100, added this project's
    // Production Readiness sprint specifically because this table
    // never existed despite the queue infrastructure around it being
    // real). `php artisan queue:failed` / `queue:retry` read from here.
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'failed_jobs',
    ],

];
