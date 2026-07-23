<?php

// AI Assistant provider configuration. Provider selection is a real
// business/deployment decision (which vendor, which model, which
// contract) this project has never been given a platform-wide mandate
// on — rather than guess at one, this defaults to 'none' (the
// deterministic keyword-grounded fallback AiAssistantService already
// had from the MVP demo sprint), with two real, working integrations
// available the moment their env vars are set: Anthropic Claude
// (AI_PROVIDER=anthropic + ANTHROPIC_API_KEY) and OpenAI
// (AI_PROVIDER=openai + OPENAI_API_KEY). Anthropic is verified
// reachable from this sandbox's network policy; OpenAI is not, but
// both integrations are written the same way — directly from each
// vendor's public API reference, request shape locked in by
// `Http::fake()` tests, never a hardcoded key. Adding another
// provider is: implement App\Services\Ai\LlmProviderInterface, add a
// config block here, wire it in AiServiceProvider — the same pattern
// every other pluggable piece of this project follows.
//
// This platform-level config is the default; a tenant can override
// which provider THEY use via ai_settings.provider_override (see
// AiSettingsService) without touching environment variables — useful
// for a platform operator who wants Tenant A on Anthropic and Tenant
// B on OpenAI from the same deployment. The override still only ever
// selects among providers that have real credentials configured here
// — a tenant can choose *which* configured provider, never supply
// their own key through the app (that stays a deployment-time secret,
// never tenant-controlled data).

return [

    'provider' => env('AI_PROVIDER', 'none'), // none|anthropic|openai

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/messages'),
        'api_version' => '2023-06-01',
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 1024),
        'timeout_seconds' => (int) env('ANTHROPIC_TIMEOUT_SECONDS', 20),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/chat/completions'),
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1024),
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 20),
    ],

    // How many prior messages from the same conversation are sent to
    // the LLM as context — bounded so a very long-running conversation
    // doesn't grow the request without limit.
    'history_window' => 10,

];
