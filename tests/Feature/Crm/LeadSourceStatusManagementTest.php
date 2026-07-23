<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadSourceStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Source Status Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_owner_can_create_a_custom_lead_source(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('source-create');
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/lead-sources', ['name_en' => 'LinkedIn', 'name_ar' => 'لينكد إن']);

        $response->assertCreated()->assertJsonPath('data.name_en', 'LinkedIn');
    }

    public function test_duplicate_source_names_within_a_tenant_are_rejected(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('source-dup');
        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/lead-sources', ['name_en' => 'Website'])
            ->assertStatus(422); // 'Website' already exists from CrmProvisioningService defaults
    }

    public function test_a_lead_source_still_in_use_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('source-inuse');

        $source = LeadSource::where('tenant_id', $tenant->id)->first();
        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->first();
        Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_source_id' => $source->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/lead-sources/{$source->id}");

        $response->assertStatus(409);
    }

    public function test_setting_a_new_default_status_clears_the_previous_one(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('status-default');
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/lead-statuses', [
            'name_en' => 'Nurturing', 'is_default' => true,
        ]);
        $create->assertCreated();

        $this->assertDatabaseHas('lead_statuses', ['id' => $create->json('data.id'), 'is_default' => true]);
        $this->assertDatabaseHas('lead_statuses', ['tenant_id' => $tenant->id, 'name_en' => 'New', 'is_default' => false]);

        // Exactly one default per tenant.
        $this->assertEquals(1, LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->count());
    }

    public function test_the_default_status_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('status-default-delete');

        $default = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/lead-statuses/{$default->id}");

        $response->assertStatus(409);
    }
}
