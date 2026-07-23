<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Dashboard Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_dashboard_totals_reflect_real_leads(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-dash-totals');

        $newStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $wonStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();

        Lead::factory()->count(3)->create(['tenant_id' => $tenant->id, 'lead_status_id' => $newStatus->id]);
        Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $wonStatus->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.totals.total_leads', 4);
        $response->assertJsonPath('data.totals.won_this_month', 1);
        $response->assertJsonCount(7, 'data.pipeline'); // the 7 default statuses
    }

    public function test_pipeline_breakdown_groups_by_status(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-dash-pipeline');

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        Lead::factory()->count(2)->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/dashboard');

        $pipeline = collect($response->json('data.pipeline'))->firstWhere('status_id', $status->id);
        $this->assertEquals(2, $pipeline['count']);
    }

    public function test_recent_leads_are_included(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-dash-recent');

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id, 'first_name' => 'Fresh Lead']);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/dashboard');

        $names = collect($response->json('data.recent_leads.data'))->pluck('first_name');
        $this->assertTrue($names->contains('Fresh Lead'));
    }
}
