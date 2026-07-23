<?php

// Third-party service credentials this application talks to over
// HTTP — mirrors config/ai.php's pattern exactly: env-driven only,
// never a hardcoded key, real integrations rather than placeholders.

return [

    'error_tracking' => [
        // A generic webhook URL, not a vendor-specific SDK config —
        // see App\Services\ErrorTrackingService for why (composer
        // install is blocked in this sandbox, so no Sentry/Bugsnag
        // SDK can be vendored). Leave unset to rely on log-only error
        // visibility (a legitimate choice for a smaller deployment),
        // or point it at a real Sentry-compatible ingest endpoint, a
        // generic webhook relay, or an internal alerting service.
        'webhook_url' => env('ERROR_TRACKING_WEBHOOK_URL'),
    ],

];
