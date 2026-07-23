<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\Role;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityManagementTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Opportunity Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_registration_provisions_default_opportunity_stages(): void
    {
        [$tenant] = $this->registerTenantWithOwner('opp-provision');

        $this->assertDatabaseHas('opportunity_stages', ['tenant_id' => $tenant->id, 'name_en' => 'Qualification', 'is_default' => true]);
        $this->assertDatabaseHas('opportunity_stages', ['tenant_id' => $tenant->id, 'name_en' => 'Closed Won', 'is_won' => true]);
        $this->assertDatabaseHas('opportunity_stages', ['tenant_id' => $tenant->id, 'name_en' => 'Closed Lost', 'is_lost' => true]);
    }

    public function test_owner_can_create_an_opportunity_against_a_customer(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-create');

        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/opportunities', [
            'name' => 'Acme — Annual License',
            'customer_id' => $customer->id,
            'amount' => 50000,
            'expected_close_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertCreated();
        $this->assertMatchesRegularExpression('/^OP-\d{6}$/', $response->json('data.opportunity_number'));
        // Probability defaults from the stage's default_probability when not specified.
        $this->assertEquals(10, $response->json('data.probability'));
        $this->assertDatabaseHas('opportunity_activities', [
            'opportunity_id' => $response->json('data.id'), 'type' => 'created',
        ]);
    }

    public function test_full_crud_lifecycle(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-crud');
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/opportunities', [
            'name' => 'Test Deal', 'customer_id' => $customer->id,
        ]);
        $opportunityId = $create->json('data.id');

        $show = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/opportunities/{$opportunityId}");
        $show->assertOk()->assertJsonPath('data.name', 'Test Deal');

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/opportunities/{$opportunityId}", ['amount' => 75000]);
        $update->assertOk()->assertJsonPath('data.amount', 75000);

        $delete = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/opportunities/{$opportunityId}");
        $delete->assertStatus(204);
        $this->assertSoftDeleted('opportunities', ['id' => $opportunityId]);
    }

    public function test_moving_to_a_won_stage_sets_closed_at_and_logs_a_won_activity(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-won');

        $wonStage = OpportunityStage::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $opportunity = Opportunity::factory()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/opportunities/{$opportunity->id}", ['stage_id' => $wonStage->id]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.closed_at'));
        $this->assertDatabaseHas('opportunity_activities', ['opportunity_id' => $opportunity->id, 'type' => 'won']);
    }

    public function test_a_sales_role_can_only_see_opportunities_assigned_to_them(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('opp-sales-scope');

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $salesRole = Role::where('tenant_id', $tenant->id)->where('code', Role::SALES)->firstOrFail();

        $repA = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);
        $repB = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $oppForA = Opportunity::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'assigned_to_user_id' => $repA->id]);
        $oppForB = Opportunity::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'assigned_to_user_id' => $repB->id]);

        Sanctum::actingAs($repA);

        $index = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/opportunities');
        $ids = collect($index->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($oppForA->id));
        $this->assertFalse($ids->contains($oppForB->id));

        $showOthers = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/opportunities/{$oppForB->id}");
        $showOthers->assertStatus(403);
    }
}
