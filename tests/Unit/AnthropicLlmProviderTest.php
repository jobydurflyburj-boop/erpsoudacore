<?php

namespace Tests\Unit;

use App\Services\Ai\AnthropicLlmProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AnthropicLlmProvider has never actually been exercised against the
 * real api.anthropic.com endpoint in this sandbox (no API key is
 * configured here, and the application layer never executes at all —
 * the standing `composer install`-blocked constraint every other
 * piece of real application code in this project carries). What CAN
 * be verified for real: that the request this code sends matches
 * Anthropic's public API reference exactly — the right URL, the right
 * headers, the right JSON body shape — via `Http::fake()`, which
 * intercepts the real HTTP client this class uses rather than a mock
 * of the class itself.
 */
class AnthropicLlmProviderTest extends TestCase
{
    public function test_is_not_configured_without_an_api_key(): void
    {
        config(['ai.anthropic.api_key' => null]);

        $this->assertFalse((new AnthropicLlmProvider())->isConfigured());
    }

    public function test_is_configured_with_an_api_key(): void
    {
        config(['ai.anthropic.api_key' => 'sk-test-key']);

        $this->assertTrue((new AnthropicLlmProvider())->isConfigured());
    }

    public function test_complete_sends_the_exact_request_shape_anthropics_api_expects(): void
    {
        config([
            'ai.anthropic.api_key' => 'sk-test-key',
            'ai.anthropic.model' => 'claude-sonnet-5',
            'ai.anthropic.base_url' => 'https://api.anthropic.com/v1/messages',
            'ai.anthropic.api_version' => '2023-06-01',
            'ai.anthropic.max_tokens' => 1024,
            'ai.anthropic.timeout_seconds' => 20,
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'You have 3 open leads.']],
            ], 200),
        ]);

        $provider = new AnthropicLlmProvider();
        $result = $provider->complete(
            'You are a helpful assistant.',
            [['role' => 'user', 'content' => 'Hi'], ['role' => 'assistant', 'content' => 'Hello!']],
            'How many leads do we have?'
        );

        $this->assertEquals('You have 3 open leads.', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'sk-test-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request['model'] === 'claude-sonnet-5'
                && $request['max_tokens'] === 1024
                && $request['system'] === 'You are a helpful assistant.'
                && count($request['messages']) === 3 // 2 history + 1 new
                && $request['messages'][2]['role'] === 'user'
                && $request['messages'][2]['content'] === 'How many leads do we have?';
        });
    }

    public function test_complete_throws_on_a_failed_response_rather_than_returning_garbage(): void
    {
        config(['ai.anthropic.api_key' => 'sk-test-key']);

        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $this->expectException(\RuntimeException::class);

        (new AnthropicLlmProvider())->complete('System prompt', [], 'Hello');
    }

    public function test_complete_throws_when_the_api_key_is_missing_rather_than_silently_calling_out(): void
    {
        config(['ai.anthropic.api_key' => null]);
        Http::fake();

        try {
            (new AnthropicLlmProvider())->complete('System prompt', [], 'Hello');
            $this->fail('Expected a RuntimeException when no API key is configured.');
        } catch (\RuntimeException $e) {
            // Expected — and confirm no HTTP call was ever attempted with a missing key.
            Http::assertNothingSent();
        }
    }
}
