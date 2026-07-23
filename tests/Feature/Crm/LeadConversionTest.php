<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Conversion Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_a_won_lead_can_be_converted_to_a_customer(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('convert-happy');

        $wonStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();
        $lead = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $wonStatus->id,
            'company_name' => 'Won Deal LLC', 'first_name' => 'Reem', 'last_name' => 'Al-Dosari',
            'assigned_to_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/convert-to-customer");

        $response->assertCreated();
        $response->assertJsonPath('data.company_name', 'Won Deal LLC');
        $response->assertJsonPath('data.first_name', 'Reem');
        $response->assertJsonPath('data.account_manager.id', $owner->id);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'converted_to_customer_id' => $response->json('data.id'),
        ]);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'converted']);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $response->json('data.id'), 'type' => 'converted_from_lead',
        ]);
        $this->assertDatabaseHas('customers', ['source_lead_id' => $lead->id]);
    }

    public function test_a_lead_not_marked_won_cannot_be_converted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('convert-not-won');

        $newStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $newStatus->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/convert-to-customer");

        $response->assertStatus(422);
        $this->assertDatabaseMissing('customers', ['source_lead_id' => $lead->id]);
    }

    public function test_a_lead_cannot_be_converted_twice(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('convert-twice');

        $wonStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $wonStatus->id]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/convert-to-customer")
            ->assertCreated();

        $second = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/convert-to-customer");

        $second->assertStatus(422);
        $this->assertEquals(1, \App\Models\Customer::where('source_lead_id', $lead->id)->count());
    }

    public function test_conversion_overrides_are_applied_on_top_of_lead_data(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('convert-overrides');

        $wonStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();
        $lead = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $wonStatus->id, 'company_name' => 'Original Name',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/convert-to-customer", [
                'company_name' => 'Corrected Name Before Conversion',
                'payment_terms_days' => 60,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.company_name', 'Corrected Name Before Conversion');
        $response->assertJsonPath('data.payment_terms_days', 60);
    }

    public function test_a_sales_role_cannot_convert_a_lead_that_is_not_theirs(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('convert-forbidden');

        $company = \App\Models\Company::where('tenant_id', $tenant->id)->firstOrFail();
        $salesRole = \App\Models\Role::where('tenant_id', $tenant->id)->where('code', \App\Models\Role::SALES)->firstOrFail();
        $repA = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => \Illuminate\Support\Facades\Hash::make('irrelevant'),
        ]);

        $wonStatus = LeadStatus::where('tenant_id', $tenant->id)->where('is_won', true)->firstOrFail();
        $leadOwnedByOwner = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $wonStatus->id, 'assigned_to_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($repA);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$leadOwnedByOwner->id}/convert-to-customer");

        $response->assertStatus(403);
    }
}
