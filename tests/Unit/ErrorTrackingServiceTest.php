<?php

namespace Tests\Unit;

use App\Services\ErrorTrackingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErrorTrackingServiceTest extends TestCase
{
    public function test_it_does_nothing_when_no_webhook_is_configured(): void
    {
        config(['services.error_tracking.webhook_url' => null]);
        Http::fake();

        app(ErrorTrackingService::class)->report(new \RuntimeException('test error'));

        Http::assertNothingSent();
    }

    public function test_it_posts_a_real_structured_payload_when_a_webhook_is_configured(): void
    {
        config(['services.error_tracking.webhook_url' => 'https://errors.example.com/ingest']);
        Http::fake(['errors.example.com/*' => Http::response(['ok' => true], 200)]);

        app(ErrorTrackingService::class)->report(new \RuntimeException('a real test error'), ['extra' => 'context']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://errors.example.com/ingest'
                && $request['message'] === 'a real test error'
                && $request['exception_class'] === 'RuntimeException'
                && $request['context']['extra'] === 'context';
        });
    }

    public function test_a_failed_webhook_delivery_never_throws(): void
    {
        config(['services.error_tracking.webhook_url' => 'https://errors.example.com/ingest']);
        Http::fake(['errors.example.com/*' => Http::response([], 500)]);

        // No exception should escape this call even though the webhook delivery itself fails.
        app(ErrorTrackingService::class)->report(new \RuntimeException('test error'));

        $this->assertTrue(true);
    }
}
