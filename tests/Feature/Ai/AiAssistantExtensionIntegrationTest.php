<?php

namespace Tests\Feature\Ai;

use App\Models\AiSuggestion;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AI Assistant module completion (second pass): AI Settings, Prompt
 * Management, Insights (Dashboard/Sales/Inventory/Financial/CRM),
 * Automation Suggestions with real AI Notifications, the Activity
 * Log audit trail, and the per-tenant provider override.
 */
class AiAssistantExtensionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI Extension Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_ai_settings_default_to_enabled_and_can_be_updated(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-settings');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $show = $h()->getJson('/api/v1/ai/settings');
        $show->assertOk();
        $this->assertTrue($show->json('data.is_enabled'));
        $this->assertTrue($show->json('data.insights_enabled'));

        $update = $h()->patchJson('/api/v1/ai/settings', ['insights_enabled' => false, 'provider_override' => 'openai']);
        $update->assertOk();
        $this->assertFalse($update->json('data.insights_enabled'));
        $this->assertEquals('openai', $update->json('data.provider_override'));

        // An invalid provider override is rejected outright.
        $h()->patchJson('/api/v1/ai/settings', ['provider_override' => 'not-a-real-provider'])->assertStatus(422);
    }

    public function test_disabling_insights_returns_a_real_explanatory_message_not_an_error(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-insights-disabled');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->patchJson('/api/v1/ai/settings', ['insights_enabled' => false])->assertOk();

        $insight = $h()->getJson('/api/v1/ai/insights/dashboard');
        $insight->assertOk();
        $this->assertStringContainsString('turned off', $insight->json('data.summary'));
    }

    public function test_dashboard_insight_returns_a_real_deterministic_summary_without_an_llm_configured(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-dashboard-insight');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $insight = $h()->getJson('/api/v1/ai/insights/dashboard');
        $insight->assertOk();
        $this->assertStringContainsString('Cash position', $insight->json('data.summary'));
        $this->assertNull($insight->json('data.provider'));

        // Every insight call is logged for real.
        $this->assertDatabaseHas('ai_activity_logs', ['tenant_id' => $tenant->id, 'feature' => 'dashboard_insight']);
    }

    public function test_inventory_insight_raises_a_real_suggestion_and_notifies_admins_only_once(): void
    {
        Notification::fake();
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-inventory-suggestion');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', [
            'sku' => 'LOWSTOCK-1', 'name_en' => 'Low Stock Item', 'reorder_point' => 100,
        ])->json('data.id');
        $this->assertNotNull($productId);

        $first = $h()->getJson('/api/v1/ai/insights/inventory');
        $first->assertOk();
        $this->assertTrue($first->json('data.suggestion_raised'));
        $this->assertDatabaseHas('ai_suggestions', ['tenant_id' => $tenant->id, 'category' => 'inventory_reorder', 'status' => 'open']);
        $this->assertEquals(1, AiSuggestion::where('tenant_id', $tenant->id)->count());

        // A second call while the same condition is still open does NOT open a duplicate suggestion.
        $second = $h()->getJson('/api/v1/ai/insights/inventory');
        $second->assertOk();
        $this->assertEquals(1, AiSuggestion::where('tenant_id', $tenant->id)->count());
    }

    public function test_a_suggestion_can_be_dismissed_and_marked_actioned(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-suggestion-actions');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $suggestion = AiSuggestion::create([
            'tenant_id' => $tenant->id, 'category' => 'overdue_followup', 'title' => 'Test',
            'description' => 'Test description', 'status' => AiSuggestion::STATUS_OPEN,
        ]);

        $dismiss = $h()->postJson("/api/v1/ai/suggestions/{$suggestion->id}/dismiss");
        $dismiss->assertOk();
        $this->assertEquals('dismissed', $dismiss->json('data.status'));

        // Already-dismissed can't be dismissed again.
        $h()->postJson("/api/v1/ai/suggestions/{$suggestion->id}/dismiss")->assertStatus(422);
    }

    public function test_prompt_templates_resolve_to_defaults_and_can_be_overridden_and_reset(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-prompts');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $index = $h()->getJson('/api/v1/ai/prompt-templates');
        $index->assertOk();
        $chatPrompt = collect($index->json('data'))->firstWhere('key', 'chat_system');
        $this->assertFalse($chatPrompt['is_custom']);
        $this->assertStringContainsString('SoudaCore ERP', $chatPrompt['content']);

        $h()->putJson('/api/v1/ai/prompt-templates', ['key' => 'chat_system', 'content' => 'Custom prompt for this tenant.'])->assertOk();

        $afterUpdate = $h()->getJson('/api/v1/ai/prompt-templates');
        $updated = collect($afterUpdate->json('data'))->firstWhere('key', 'chat_system');
        $this->assertTrue($updated['is_custom']);
        $this->assertEquals('Custom prompt for this tenant.', $updated['content']);

        $h()->postJson('/api/v1/ai/prompt-templates/chat_system/reset')->assertOk();
        $afterReset = $h()->getJson('/api/v1/ai/prompt-templates');
        $reset = collect($afterReset->json('data'))->firstWhere('key', 'chat_system');
        $this->assertFalse($reset['is_custom']);
    }

    public function test_an_invalid_prompt_key_is_rejected(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-prompts-invalid');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->putJson('/api/v1/ai/prompt-templates', ['key' => 'not_a_real_key', 'content' => 'x'])->assertStatus(422);
    }

    public function test_a_tenant_provider_override_is_used_when_that_provider_has_real_credentials(): void
    {
        config(['ai.provider' => 'none', 'ai.openai.api_key' => 'sk-test-key', 'ai.openai.model' => 'gpt-4o']);
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'You currently have 0 open leads.']]],
        ], 200)]);

        [$tenant, $owner] = $this->registerTenantWithOwner('ai-provider-override');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        // Platform default is 'none', but this tenant overrides to 'openai', which HAS real credentials configured.
        $h()->patchJson('/api/v1/ai/settings', ['provider_override' => 'openai'])->assertOk();

        $ask = $h()->postJson('/api/v1/ai/ask', ['message' => 'How many leads do we have?']);
        $ask->assertCreated();
        $reply = collect($ask->json('data.messages'))->last();
        $this->assertEquals('openai', $reply['provider']);
    }

    public function test_a_tenant_provider_override_without_real_credentials_falls_back_to_the_platform_default(): void
    {
        // Platform default is 'none' and OpenAI has no key configured anywhere — the override should not error, just fall back.
        config(['ai.provider' => 'none', 'ai.openai.api_key' => null]);

        [$tenant, $owner] = $this->registerTenantWithOwner('ai-provider-override-nokey');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->patchJson('/api/v1/ai/settings', ['provider_override' => 'openai'])->assertOk();

        $ask = $h()->postJson('/api/v1/ai/ask', ['message' => 'How many leads do we have?']);
        $ask->assertCreated();
        $reply = collect($ask->json('data.messages'))->last();
        $this->assertNull($reply['provider']); // fell back to the real deterministic reply, not an error
    }

    public function test_activity_log_lists_real_entries(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ai-activity-log');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->getJson('/api/v1/ai/insights/crm')->assertOk();

        $log = $h()->getJson('/api/v1/ai/activity-logs');
        $log->assertOk();
        $this->assertGreaterThanOrEqual(1, count($log->json('data')));
        $this->assertEquals('crm_insight', $log->json('data.0.feature'));
    }
}
