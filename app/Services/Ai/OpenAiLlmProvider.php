<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A real, working integration against OpenAI's Chat Completions API
 * (https://api.openai.com/v1/chat/completions), mirroring
 * AnthropicLlmProvider's exact structure and safety properties: no
 * SDK (composer install is blocked), no hardcoded key (config only),
 * loud failure on a missing key, a RuntimeException on any HTTP
 * failure rather than returning garbage. Request shape verified via
 * `Http::fake()` in OpenAiLlmProviderTest — this project's network
 * policy does not allow api.openai.com specifically, so unlike
 * Anthropic this has not even been reachable to attempt in this
 * sandbox; the request shape is written directly from OpenAI's public
 * API reference regardless.
 */
class OpenAiLlmProvider implements LlmProviderInterface
{
    public function isConfigured(): bool
    {
        return filled(config('ai.openai.api_key'));
    }

    public function complete(string $systemPrompt, array $history, string $userMessage): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenAI provider called without an API key configured.');
        }

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::withToken(config('ai.openai.api_key'))
            ->timeout(config('ai.openai.timeout_seconds'))
            ->post(config('ai.openai.base_url'), [
                'model' => config('ai.openai.model'),
                'max_tokens' => config('ai.openai.max_tokens'),
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("OpenAI API request failed with status {$response->status()}: ".$response->body());
        }

        $text = (string) $response->json('choices.0.message.content', '');

        if ($text === '') {
            throw new RuntimeException('OpenAI API returned no message content.');
        }

        return $text;
    }

    public function name(): string { return 'openai'; }
    public function model(): string { return (string) config('ai.openai.model'); }
}
