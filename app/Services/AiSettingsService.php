<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\User;
use InvalidArgumentException;

/**
 * Real per-tenant AI configuration — one row per tenant, created
 * lazily on first access rather than at registration, so every tenant
 * (including ones that registered before this sprint) gets sensible
 * defaults (AI enabled, insights/notifications/automation-suggestions
 * enabled, no provider override) without a backfill command.
 */
class AiSettingsService
{
    public function get(string $tenantId): AiSetting
    {
        return AiSetting::firstOrCreate(['tenant_id' => $tenantId]);
    }

    public function update(User $actor, array $data): AiSetting
    {
        if (array_key_exists('provider_override', $data) && $data['provider_override'] !== null) {
            $allowed = ['anthropic', 'openai'];
            if (! in_array($data['provider_override'], $allowed, true)) {
                throw new InvalidArgumentException("provider_override must be one of: ".implode(', ', $allowed).', or null to use the platform default.');
            }
        }

        $setting = $this->get($actor->tenant_id);
        $setting->update($data);

        return $setting->fresh();
    }
}
