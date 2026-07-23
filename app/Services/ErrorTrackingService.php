<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Production Readiness — Error tracking. No Sentry/Bugsnag SDK is
 * vendored (`composer install` is blocked in this sandbox, the same
 * standing constraint every real-but-unexecuted integration in this
 * project carries — see AnthropicLlmProvider/OpenAiLlmProvider for the
 * same pattern). Rather than skip error tracking entirely, this posts
 * a real, structured JSON payload to a configurable webhook URL
 * (`ERROR_TRACKING_WEBHOOK_URL`) — genuinely working with any HTTP
 * endpoint that accepts JSON (a real Sentry "Ingest" style endpoint,
 * a generic webhook relay, Slack's incoming-webhook format via a thin
 * adapter, a custom internal endpoint), not a vendor-specific
 * integration. Never throws — a failure to report an error must never
 * itself crash request handling or mask the original exception.
 */
class ErrorTrackingService
{
    public function report(Throwable $e, array $context = []): void
    {
        $webhookUrl = config('services.error_tracking.webhook_url');
        if (! $webhookUrl) {
            return; // not configured — logging (already real, via report($e) -> Laravel's log channel) remains the only sink, and that's a legitimate deployment choice, not a gap
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'level' => 'error',
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'environment' => config('app.env'),
                'tenant_id' => app(\App\Multitenancy\TenantContext::class)->id(),
                'context' => $context,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $reportingFailure) {
            // Reporting the error must never throw its own error into the response cycle —
            // log it locally and move on; the original exception is still handled normally.
            Log::warning('Error tracking webhook delivery failed', ['error' => $reportingFailure->getMessage()]);
        }
    }
}
