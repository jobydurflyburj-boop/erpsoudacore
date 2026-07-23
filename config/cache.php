<?php

// Final Production Validation pass: this file was missing entirely —
// CACHE_STORE=redis has been the real default in .env.example since
// Foundation, and real code depends on it (Role::cachedPermissionKeys(),
// the AI Assistant's per-tenant provider-override resolution, the
// health check's own cache round-trip check) — but the store
// definitions themselves were never published, only relying on
// Laravel's unpublished internal default. Publishing this makes the
// real cache prefix (tenant-safe — see the note below) and store
// definitions visible and auditable.

return [

    'default' => env('CACHE_STORE', 'database'),

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    // A real, non-empty prefix — deliberately NOT tenant-specific
    // (cache keys that need per-tenant isolation, e.g. any future
    // per-tenant cached data, are the responsibility of the code that
    // builds those keys to include the tenant id in, the same way
    // every RLS-backed query is tenant-scoped by the database, not by
    // the cache layer). This prefix exists to prevent key collisions
    // between this application and anything else sharing the same
    // Redis instance, not to provide tenant isolation itself.
    'prefix' => env('CACHE_PREFIX', \Illuminate\Support\Str::slug(env('APP_NAME', 'soudacore'), '_').'_cache_'),

];
