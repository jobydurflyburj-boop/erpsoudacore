<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Assign Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_assigning_a_lead_creates_a_timeline_entry_and_notifies_the_assignee(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('assign-notify');

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $salesRole = Role::where('tenant_id', $tenant->id)->where('code', Role::SALES)->firstOrFail();
        $salesperson = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $salesRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/v1/crm/leads/{$lead->id}/assign", ['assigned_to_user_id' => $salesperson->id]);

        $response->assertOk()->assertJsonPath('data.assignee.id', $salesperson->id);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id, 'type' => 'assigned',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $salesperson->id, 'category' => 'lead.assigned',
        ]);
    }

    public function test_reassigning_to_the_same_user_is_a_no_op(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('assign-noop');

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $lead = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $status->id, 'assigned_to_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/v1/crm/leads/{$lead->id}/assign", ['assigned_to_user_id' => $owner->id])
            ->assertOk();

        $this->assertDatabaseMissing('lead_activities', ['lead_id' => $lead->id, 'type' => 'assigned']);
    }

    public function test_a_salesperson_cannot_assign_a_lead_that_is_not_theirs(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('assign-forbidden');

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
        $leadOwnedByB = Lead::factory()->create([
            'tenant_id' => $tenant->id, 'lead_status_id' => $status->id, 'assigned_to_user_id' => $salesB->id,
        ]);

        Sanctum::actingAs($salesA);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/v1/crm/leads/{$leadOwnedByB->id}/assign", ['assigned_to_user_id' => $salesA->id]);

        $response->assertStatus(403);
    }
}
