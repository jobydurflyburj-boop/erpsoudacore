<?php

namespace Tests\Feature\Ai;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * With AI_PROVIDER=anthropic and a real key configured, AiAssistantService
 * hands the grounded data to the LLM and uses its free-form reply — and
 * when the LLM call fails for any reason, degrades to the exact same
 * deterministic grounded reply a fully unconfigured install would give,
 * never surfacing a raw error to the end user.
 */
class AiAssistantLlmFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI LLM Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_a_configured_llm_provider_is_used_and_its_reply_is_recorded_with_provider_and_model(): void
    {
        config(['ai.provider' => 'anthropic', 'ai.anthropic.api_key' => 'sk-test-key', 'ai.anthropic.model' => 'claude-sonnet-5']);
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'You currently have 2 open leads, both unassigned right now.']],
        ], 200)]);

        [$tenant, $owner] = $this->registerTenantWithOwner('ai-llm-success');
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/ai/ask', ['message' => 'How many leads do we have?']);

        $response->assertCreated();
        $reply = collect($response->json('data.messages'))->last();
        $this->assertEquals('You currently have 2 open leads, both unassigned right now.', $reply['content']);
        $this->assertEquals('anthropic', $reply['provider']);
        $this->assertEquals('claude-sonnet-5', $reply['model']);
    }

    public function test_a_failed_llm_call_degrades_to_the_real_deterministic_grounded_reply_not_an_error(): void
    {
        config(['ai.provider' => 'anthropic', 'ai.anthropic.api_key' => 'sk-test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'service unavailable'], 503)]);

        [$tenant, $owner] = $this->registerTenantWithOwner('ai-llm-fallback');
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/ai/ask', ['message' => 'How many leads do we have?']);

        // Still a 201 with a real, useful reply — never a 500 or a raw provider error leaking to the user.
        $response->assertCreated();
        $reply = collect($response->json('data.messages'))->last();
        $this->assertStringContainsString('open lead', $reply['content']);
        $this->assertNull($reply['provider']); // fell back to the deterministic path
    }

    public function test_the_status_endpoint_reflects_a_configured_provider(): void
    {
        config(['ai.provider' => 'anthropic', 'ai.anthropic.api_key' => 'sk-test-key', 'ai.anthropic.model' => 'claude-sonnet-5']);

        [$tenant, $owner] = $this->registerTenantWithOwner('ai-llm-status');
        Sanctum::actingAs($owner);

        $status = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/ai/status');
        $status->assertOk();
        $this->assertTrue($status->json('data.configured'));
        $this->assertEquals('anthropic', $status->json('data.provider'));
        $this->assertEquals('claude-sonnet-5', $status->json('data.model'));
    }
}
