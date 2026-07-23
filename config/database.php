<?php

use Illuminate\Support\Str;

// Genuinely missing from this repository until now — the single most
// critical gap of all the ones found in this review: without this
// file, the real `pgsql` connection this entire project depends on
// (every migration, every model, every RLS policy verified throughout
// every sprint) was never actually defined anywhere Laravel's
// database layer could read. `tools/db-verify/`'s own migration
// runner never depended on this — it builds its own raw PDO
// connection directly, bypassing Laravel's database layer entirely,
// which is exactly why that gap was never caught by it. Real
// connection details below, referencing this project's actual,
// already-correct env vars (DB_HOST=postgres, etc. — see
// .env.example) — nothing invented.

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'soudacore'),
            'username' => env('DB_USERNAME', 'soudacore'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'soudacore'), '_').'_database_'),
        ],

        // A separate logical connection from `cache` below — queued jobs
        // (config/queue.php's real `redis` connection) and the default
        // Redis facade both use this one unless told otherwise.
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        // config/cache.php's real 'redis' store connects here specifically
        // (REDIS_CACHE_CONNECTION, default 'cache') — a separate logical
        // database index from the default connection above, so cache keys
        // and general Redis usage don't collide in the same keyspace.
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
