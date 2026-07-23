<?php

namespace App\Services;

use App\Models\AiPromptTemplate;
use App\Models\User;
use InvalidArgumentException;

/**
 * Real prompt management: a tenant can override the system prompt
 * used for chat or for any insight-generation feature. No override on
 * file for a key means the real, sensible built-in default is used —
 * never a blank or broken prompt.
 */
class AiPromptService
{
    private const VALID_KEYS = [
        AiPromptTemplate::KEY_CHAT_SYSTEM,
        AiPromptTemplate::KEY_DASHBOARD_INSIGHTS,
        AiPromptTemplate::KEY_SALES_INSIGHTS,
        AiPromptTemplate::KEY_INVENTORY_INSIGHTS,
        AiPromptTemplate::KEY_FINANCIAL_INSIGHTS,
        AiPromptTemplate::KEY_CRM_INSIGHTS,
    ];

    private function defaults(): array
    {
        return [
            AiPromptTemplate::KEY_CHAT_SYSTEM => 'You are the AI Assistant inside SoudaCore ERP, a Saudi Arabia-focused '.
                'business management system. Answer the user\'s question helpfully and concisely. Never invent figures.',
            AiPromptTemplate::KEY_DASHBOARD_INSIGHTS => 'Summarize the real business snapshot below in 2-3 short sentences '.
                'a busy owner can read in seconds. Call out anything that looks like it needs attention. Never invent figures.',
            AiPromptTemplate::KEY_SALES_INSIGHTS => 'Summarize the real sales data below in 2-3 short sentences. Call out '.
                'notable trends, top performers, or overdue amounts that need attention. Never invent figures.',
            AiPromptTemplate::KEY_INVENTORY_INSIGHTS => 'Summarize the real inventory data below in 2-3 short sentences. '.
                'Call out low-stock items or reorder needs. Never invent figures.',
            AiPromptTemplate::KEY_FINANCIAL_INSIGHTS => 'Summarize the real financial data below in 2-3 short sentences a '.
                'business owner can act on. Call out cash position, receivables, or payables that need attention. Never invent figures.',
            AiPromptTemplate::KEY_CRM_INSIGHTS => 'Summarize the real CRM pipeline data below in 2-3 short sentences. Call '.
                'out conversion trends or stalled opportunities. Never invent figures.',
        ];
    }

    public function validKeys(): array
    {
        return self::VALID_KEYS;
    }

    /** The real active prompt for a key — a tenant's saved override if one exists and is active, otherwise the built-in default. */
    public function resolve(string $tenantId, string $key): string
    {
        $custom = AiPromptTemplate::where('tenant_id', $tenantId)->where('key', $key)->where('is_active', true)->first();

        return $custom?->content ?? $this->defaults()[$key] ?? 'You are a helpful assistant. Never invent figures.';
    }

    public function upsert(User $actor, string $key, string $content): AiPromptTemplate
    {
        if (! in_array($key, self::VALID_KEYS, true)) {
            throw new InvalidArgumentException("'{$key}' is not a valid prompt template key. Valid keys: ".implode(', ', self::VALID_KEYS));
        }

        return AiPromptTemplate::updateOrCreate(
            ['tenant_id' => $actor->tenant_id, 'key' => $key],
            ['content' => $content, 'is_active' => true, 'created_by_user_id' => $actor->id]
        );
    }

    /** Real reset: deletes the tenant's override so resolve() falls back to the built-in default again. */
    public function resetToDefault(string $tenantId, string $key): void
    {
        AiPromptTemplate::where('tenant_id', $tenantId)->where('key', $key)->delete();
    }
}
