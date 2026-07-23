<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Customer Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_owner_can_create_a_customer_with_an_auto_generated_customer_number(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('cust-create');
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/customers', [
            'first_name' => 'Nasser',
            'last_name' => 'Al-Qahtani',
            'company_name' => 'Qahtani Group',
            'email' => 'nasser@qahtanigroup.test',
            'credit_limit' => 20000,
            'payment_terms_days' => 30,
        ]);

        $response->assertCreated();
        $this->assertMatchesRegularExpression('/^CU-\d{6}$/', $response->json('data.customer_number'));
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $response->json('data.id'), 'type' => 'created',
        ]);
    }

    public function test_customer_numbers_and_lead_numbers_use_independent_sequences(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('cust-seq');
        Sanctum::actingAs($owner);

        $lead = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/leads', ['first_name' => 'L1'])->json('data.lead_number');
        $customer = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/customers', ['first_name' => 'C1'])->json('data.customer_number');

        // Both are the first of their respective sequences — proves
        // SequenceService is correctly namespaced per (tenant, name),
        // not sharing one counter across entity types.
        $this->assertEquals('LD-000001', $lead);
        $this->assertEquals('CU-000001', $customer);
    }

    public function test_full_crud_lifecycle(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('cust-crud');
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/crm/customers', ['first_name' => 'Fahad']);
        $customerId = $create->json('data.id');

        $show = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/customers/{$customerId}");
        $show->assertOk()->assertJsonPath('data.first_name', 'Fahad');

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/customers/{$customerId}", ['status' => 'inactive']);
        $update->assertOk()->assertJsonPath('data.status', 'inactive');

        $delete = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/crm/customers/{$customerId}");
        $delete->assertStatus(204);
        $this->assertSoftDeleted('customers', ['id' => $customerId]);
    }

    public function test_changing_account_manager_is_logged_on_the_timeline(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('cust-manager-change');

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $salesRole = Role::where('tenant_id', $tenant->id)->where('code', Role::SALES)->firstOrFail();
        $rep = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);
        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/crm/customers', ['first_name' => 'Test']);
        $customerId = $create->json('data.id');

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/customers/{$customerId}", ['account_manager_user_id' => $rep->id])
            ->assertOk();

        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customerId, 'type' => 'account_manager_changed',
        ]);
    }

    public function test_a_sales_role_can_only_see_customers_they_manage(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('cust-sales-scope');

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

        $customerForA = Customer::factory()->create(['tenant_id' => $tenant->id, 'account_manager_user_id' => $repA->id]);
        $customerForB = Customer::factory()->create(['tenant_id' => $tenant->id, 'account_manager_user_id' => $repB->id]);

        Sanctum::actingAs($repA);

        $index = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/crm/customers');
        $ids = collect($index->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($customerForA->id));
        $this->assertFalse($ids->contains($customerForB->id));

        $showOthers = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/crm/customers/{$customerForB->id}");
        $showOthers->assertStatus(403);
    }
}
