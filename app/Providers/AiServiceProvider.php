<?php

namespace App\Providers;

use App\Models\AiSetting;
use App\Multitenancy\TenantContext;
use App\Services\Ai\AnthropicLlmProvider;
use App\Services\Ai\LlmProviderInterface;
use App\Services\Ai\NullLlmProvider;
use App\Services\Ai\OpenAiLlmProvider;
use Illuminate\Support\ServiceProvider;

/**
 * The one place `AI_PROVIDER` gets resolved into a real
 * `LlmProviderInterface` binding — everything else in the AI
 * Assistant module depends on the interface only, never on a
 * concrete provider class, so switching providers is a config change
 * plus a new implementation, not a rewrite of AiAssistantService.
 *
 * Bound with `bind()`, not `singleton()`, deliberately: the closure
 * below reads the current tenant's `ai_settings.provider_override` at
 * *resolution* time (safe — by the time any controller/service asks
 * for this interface, tenant-resolution middleware has already run),
 * so a tenant-level override takes effect immediately without a
 * container rebuild. The override can only ever select among
 * providers that have real platform-level credentials configured
 * (`isConfigured()`) — a tenant chooses *which* configured provider,
 * never supplies their own key through the app.
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LlmProviderInterface::class, function () {
            return $this->resolveProvider($this->resolveProviderName());
        });
    }

    private function resolveProviderName(): string
    {
        $platformDefault = config('ai.provider');

        $tenantId = app(TenantContext::class)->id();
        if (! $tenantId) {
            return $platformDefault;
        }

        $override = AiSetting::withoutTenantScope()->where('tenant_id', $tenantId)->value('provider_override');

        if (! $override) {
            return $platformDefault;
        }

        // Only honor the override if that provider actually has credentials configured — a tenant
        // requesting a provider the platform never set up falls back to the platform default, not an error.
        return $this->resolveProvider($override)->isConfigured() ? $override : $platformDefault;
    }

    private function resolveProvider(string $name): LlmProviderInterface
    {
        return match ($name) {
            'anthropic' => new AnthropicLlmProvider(),
            'openai' => new OpenAiLlmProvider(),
            default => new NullLlmProvider(),
        };
    }
}
