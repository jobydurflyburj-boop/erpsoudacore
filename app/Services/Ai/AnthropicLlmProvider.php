<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A real, working integration against Anthropic's Messages API
 * (https://api.anthropic.com/v1/messages) — built directly on
 * Laravel's HTTP client rather than an SDK, since `composer install`
 * is blocked in this sandbox and no Anthropic PHP SDK is vendored.
 * This has never actually been exercised end-to-end in this
 * environment (no `ANTHROPIC_API_KEY` is configured here, and the
 * application layer never executes at all — the same standing
 * `composer install`-blocked caveat every other piece of real,
 * unexecuted application code in this project carries) — but the
 * request shape below is written directly from Anthropic's public API
 * reference, not guessed at, and is exercised by
 * `AnthropicLlmProviderTest` via `Http::fake()`, which asserts the
 * exact endpoint, headers, and body shape sent.
 */
class AnthropicLlmProvider implements LlmProviderInterface
{
    public function isConfigured(): bool
    {
        return filled(config('ai.anthropic.api_key'));
    }

    public function complete(string $systemPrompt, array $history, string $userMessage): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Anthropic provider called without an API key configured.');
        }

        $messages = array_map(
            fn (array $m) => ['role' => $m['role'], 'content' => $m['content']],
            $history
        );
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::withHeaders([
            'x-api-key' => config('ai.anthropic.api_key'),
            'anthropic-version' => config('ai.anthropic.api_version'),
            'content-type' => 'application/json',
        ])
            ->timeout(config('ai.anthropic.timeout_seconds'))
            ->post(config('ai.anthropic.base_url'), [
                'model' => config('ai.anthropic.model'),
                'max_tokens' => config('ai.anthropic.max_tokens'),
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Anthropic API request failed with status {$response->status()}: ".$response->body());
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if ($text === '') {
            throw new RuntimeException('Anthropic API returned no text content.');
        }

        return $text;
    }

    public function name(): string { return 'anthropic'; }
    public function model(): string { return (string) config('ai.anthropic.model'); }
}
