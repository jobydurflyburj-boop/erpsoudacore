<?php

return [

    // Domain the platform's own routes (company registration, Super Admin
    // console, billing webhooks) resolve on — requests to this host are
    // NOT tenant-scoped.
    'central_domain' => env('CENTRAL_DOMAIN', 'soudacore.app'),

    // How a request's tenant is determined. 'subdomain' in production;
    // 'header' is used in local/dev and automated tests where wildcard
    // DNS isn't available.
    'resolution' => [
        'subdomain' => true,
        'header' => true,
        'header_name' => 'X-Tenant-ID',
    ],

    // Tenant lifecycle statuses and what each allows. Mirrors
    // tenants.status in the migration — kept here too so application code
    // never hardcodes the string literals.
    'statuses' => [
        'trial' => ['active' => true],
        'active' => ['active' => true],
        'past_due' => ['active' => true, 'warning' => true],
        'suspended' => ['active' => false],
        'cancelled' => ['active' => false],
    ],

    'trial_days' => 14,
    'grace_period_days' => 5,
];
