<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Registers a real tenant through RegistrationService (not raw
     * factories) so CrmProvisioningService's default lead sources/
     * statuses are actually in place — exercising the real provisioning
     * path rather than assuming it happened.
     */
    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'CRM Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
            'default_locale' => 'en',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_registration_provisions_default_lead_sources_and_statuses(): void
    {
        [$tenant] = $this->registerTenantWithOwner('crm-provision');

        $this->assertDatabaseHas('lead_sources', ['tenant_id' => $tenant->id, 'name_en' => 'Website']);
        $this->assertDatabaseHas('lead_statuses', ['tenant_id' => $tenant->id, 'name_en' => 'New', 'is_default' => true]);
        $this->assertDatabaseHas('lead_statuses', ['tenant_id' => $tenant->id, 'name_en' => 'Won', 'is_won' => true]);
    }

    public function test_owner_can_create_a_lead_with_an_auto_generated_lead_number(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-create');
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/leads', [
            'first_name' => 'Khalid',
            'last_name' => 'Al-Otaibi',
            'company_name' => 'Otaibi Trading',
            'email' => 'khalid@otaibitrading.test',
            'phone' => '0501112222',
            'expected_revenue' => 50000,
            'probability' => 40,
            'priority' => 'high',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', 'Khalid');
        $this->assertMatchesRegularExpression('/^LD-\d{6}$/', $response->json('data.lead_number'));

        $this->assertDatabaseHas('leads', [
            'id' => $response->json('data.id'),
            'tenant_id' => $tenant->id,
            'created_by_user_id' => $owner->id,
        ]);

        // Creating a lead writes a 'created' timeline entry — see LeadService::create.
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $response->json('data.id'), 'type' => 'created',
        ]);
    }

    public function test_lead_numbers_increment_sequentially_per_tenant(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-sequence');
        Sanctum::actingAs($owner);

        $first = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/leads', ['first_name' => 'One'])->json('data.lead_number');
        $second = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/leads', ['first_name' => 'Two'])->json('data.lead_number');

        $firstNum = (int) substr($first, 3);
        $secondNum = (int) substr($second, 3);

        $this->assertEquals($firstNum + 1, $secondNum);
    }

    public function test_a_lead_cannot_reference_another_tenants_lead_source(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-crosscheck');
        $otherTenant = Tenant::factory()->active()->create();
        $otherSource = LeadSource::factory()->for($otherTenant)->create();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/leads', [
            'first_name' => 'Test',
            'lead_source_id' => $otherSource->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_full_crud_lifecycle(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-crud');
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/leads', ['first_name' => 'Sara', 'last_name' => 'Al-Harbi']);
        $leadId = $create->json('data.id');

        $show = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/leads/{$leadId}");
        $show->assertOk()->assertJsonPath('data.first_name', 'Sara');

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/leads/{$leadId}", ['priority' => 'urgent', 'notes' => 'Follow up tomorrow']);
        $update->assertOk()->assertJsonPath('data.priority', 'urgent');

        $delete = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/leads/{$leadId}");
        $delete->assertStatus(204);
        $this->assertSoftDeleted('leads', ['id' => $leadId]);
    }

    public function test_a_sales_role_can_only_see_leads_assigned_to_them(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('crm-sales-scope');

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $salesRole = Role::where('tenant_id', $tenant->id)->where('code', Role::SALES)->firstOrFail();

        $salesA = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);
        $salesB = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();

        $leadForA = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $status->id, 'assigned_to_user_id' => $salesA->id,
        ]);
        $leadForB = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $status->id, 'assigned_to_user_id' => $salesB->id,
        ]);

        Sanctum::actingAs($salesA);

        $index = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/leads');
        $ids = collect($index->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($leadForA->id));
        $this->assertFalse($ids->contains($leadForB->id));

        $showOwn = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/leads/{$leadForA->id}");
        $showOwn->assertOk();

        $showOthers = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/leads/{$leadForB->id}");
        $showOthers->assertStatus(403);
    }

    public function test_a_role_without_crm_permissions_is_denied(): void
    {
        [$tenant] = $this->registerTenantWithOwner('crm-denied');

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $hrRole = Role::where('tenant_id', $tenant->id)->where('code', Role::HR)->firstOrFail(); // no crm.* grants

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $hrRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/leads');
        $response->assertStatus(403);
    }
}
