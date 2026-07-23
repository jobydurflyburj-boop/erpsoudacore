<?php

namespace App\Services\Ai;

/**
 * The one seam that makes provider selection a config change, not a
 * rewrite — every LLM vendor's chat API differs, but they all reduce
 * to "system prompt + conversation history + a new user message goes
 * in, assistant text comes out". Implementations own their own
 * request/response shape entirely; callers only ever see this
 * interface.
 */
interface LlmProviderInterface
{
    /** False when no API key (or equivalent) is configured — the caller falls back to the deterministic grounded reply, never crashes on a missing credential. */
    public function isConfigured(): bool;

    /**
     * @param array<int, array{role: string, content: string}> $history oldest-first, 'user'/'assistant' roles only
     * @throws \RuntimeException on any provider/network failure — callers must catch this and degrade gracefully, never surface it raw to the end user
     */
    public function complete(string $systemPrompt, array $history, string $userMessage): string;

    public function name(): string;
    public function model(): string;
}
