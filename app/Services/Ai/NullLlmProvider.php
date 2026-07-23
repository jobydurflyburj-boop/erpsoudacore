<?php

namespace App\Services\Ai;

/**
 * The default binding when AI_PROVIDER=none (or unset) — always
 * reports unconfigured, so AiAssistantService always takes the
 * deterministic keyword-grounded path. This is what the AI Assistant
 * ran on entirely from the MVP demo sprint through the Reports &
 * Analytics sprint, and remains a fully real, working mode on its
 * own, not just a stub waiting for a "real" provider.
 */
class NullLlmProvider implements LlmProviderInterface
{
    public function isConfigured(): bool { return false; }

    public function complete(string $systemPrompt, array $history, string $userMessage): string
    {
        throw new \RuntimeException('No LLM provider is configured — call isConfigured() first.');
    }

    public function name(): string { return 'none'; }
    public function model(): string { return 'none'; }
}
