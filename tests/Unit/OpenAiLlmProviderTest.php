<?php

namespace Tests\Unit;

use App\Services\Ai\OpenAiLlmProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * OpenAiLlmProvider has never been reachable from this sandbox at all
 * — api.openai.com is not in the network policy's allowed domains
 * (unlike api.anthropic.com). What CAN be verified for real: that the
 * request this code sends matches OpenAI's public Chat Completions
 * API reference exactly, via `Http::fake()` intercepting the real
 * HTTP client this class uses.
 */
class OpenAiLlmProviderTest extends TestCase
{
    public function test_is_not_configured_without_an_api_key(): void
    {
        config(['ai.openai.api_key' => null]);
        $this->assertFalse((new OpenAiLlmProvider())->isConfigured());
    }

    public function test_complete_sends_the_exact_request_shape_openais_api_expects(): void
    {
        config([
            'ai.openai.api_key' => 'sk-test-key',
            'ai.openai.model' => 'gpt-4o',
            'ai.openai.base_url' => 'https://api.openai.com/v1/chat/completions',
            'ai.openai.max_tokens' => 1024,
            'ai.openai.timeout_seconds' => 20,
        ]);

        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'You have 3 open leads.']]],
        ], 200)]);

        $provider = new OpenAiLlmProvider();
        $result = $provider->complete('You are a helpful assistant.', [['role' => 'user', 'content' => 'Hi']], 'How many leads?');

        $this->assertEquals('You have 3 open leads.', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-test-key')
                && $request['model'] === 'gpt-4o'
                && $request['messages'][0]['role'] === 'system'
                && $request['messages'][0]['content'] === 'You are a helpful assistant.'
                && $request['messages'][1]['role'] === 'user'
                && $request['messages'][2]['role'] === 'user'
                && $request['messages'][2]['content'] === 'How many leads?';
        });
    }

    public function test_complete_throws_on_a_failed_response(): void
    {
        config(['ai.openai.api_key' => 'sk-test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        try {
            (new OpenAiLlmProvider())->complete('System', [], 'Hello');
            $this->fail('Expected a RuntimeException on a failed OpenAI response.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('429', $e->getMessage());
        }
    }

    public function test_complete_throws_when_the_api_key_is_missing(): void
    {
        config(['ai.openai.api_key' => null]);
        Http::fake();

        try {
            (new OpenAiLlmProvider())->complete('System', [], 'Hello');
            $this->fail('Expected a RuntimeException when no API key is configured.');
        } catch (\RuntimeException $e) {
            Http::assertNothingSent();
        }
    }
}
