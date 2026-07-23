<?php

// Genuinely missing from this repository until now — real, standard
// Laravel 12 default, tuned for this project's real shape: a pure
// JSON API (routes/api.php) whose only real frontend
// (resources/views/app/console.blade.php,
// resources/views/super-admin/console.blade.php) talks to it via
// fetch() with a bearer token, potentially from a different
// subdomain/origin than the API itself in a real multi-tenant
// deployment (tenant subdomains vs. a central API host, depending on
// how DNS is actually set up — see config/tenancy.php).

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
