<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityStageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Stage Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_setting_a_new_default_stage_clears_the_previous_one(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-stage-default');
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/opportunity-stages', [
            'name_en' => 'Discovery Call', 'is_default' => true,
        ]);
        $create->assertCreated();

        $this->assertDatabaseHas('opportunity_stages', ['id' => $create->json('data.id'), 'is_default' => true]);
        $this->assertDatabaseHas('opportunity_stages', ['tenant_id' => $tenant->id, 'name_en' => 'Qualification', 'is_default' => false]);
        $this->assertEquals(1, OpportunityStage::where('tenant_id', $tenant->id)->where('is_default', true)->count());
    }

    public function test_a_stage_still_in_use_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-stage-inuse');

        $stage = OpportunityStage::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Opportunity::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'stage_id' => $stage->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/opportunity-stages/{$stage->id}");

        $response->assertStatus(409);
    }

    public function test_the_default_stage_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-stage-default-delete');

        $default = OpportunityStage::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/opportunity-stages/{$default->id}");

        $response->assertStatus(409);
    }
}
