<?php

namespace Tests\Feature\Ai;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_asking_about_leads_returns_a_real_computed_count(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI Test Co', 'subdomain' => 'ai-leads',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@ai-leads.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        Lead::factory()->count(3)->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/ai/ask', [
            'message' => 'How many leads do we have?',
        ]);

        $response->assertCreated();
        $reply = collect($response->json('data.messages'))->last();
        $this->assertStringContainsString('3 open lead', $reply['content']);
    }

    public function test_conversation_history_persists_across_messages(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI History Co', 'subdomain' => 'ai-history',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@ai-history.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $first = $h()->postJson('/api/v1/ai/ask', ['message' => 'How many customers do we have?']);
        $conversationId = $first->json('data.id');

        $second = $h()->postJson('/api/v1/ai/ask', ['message' => 'What about stock?', 'conversation_id' => $conversationId]);

        $second->assertCreated();
        $this->assertCount(4, $second->json('data.messages')); // 2 user + 2 assistant
    }

    public function test_asking_about_cash_and_payroll_uses_the_new_grounded_intents(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI Expanded Co', 'subdomain' => 'ai-expanded',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@ai-expanded.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $cash = $h()->postJson('/api/v1/ai/ask', ['message' => 'What is our cash position?']);
        $cash->assertCreated();
        $cashReply = collect($cash->json('data.messages'))->last();
        $this->assertStringContainsString('Cash position is SAR', $cashReply['content']);
        // No LLM configured in tests — a real, deterministic reply, provider/model both null.
        $this->assertNull($cashReply['provider']);

        $payroll = $h()->postJson('/api/v1/ai/ask', ['message' => 'How many active employees do we have?']);
        $payroll->assertCreated();
        $payrollReply = collect($payroll->json('data.messages'))->last();
        $this->assertStringContainsString('active employee', $payrollReply['content']);
    }

    public function test_ai_status_endpoint_reports_no_provider_configured_by_default(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI Status Co', 'subdomain' => 'ai-status',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@ai-status.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        Sanctum::actingAs($result['user']);

        $status = $this->withHeader('X-Tenant-ID', $result['tenant']->id)->getJson('/api/v1/ai/status');
        $status->assertOk();
        $this->assertFalse($status->json('data.configured'));
        $this->assertEquals('none', $status->json('data.provider'));
    }
}
