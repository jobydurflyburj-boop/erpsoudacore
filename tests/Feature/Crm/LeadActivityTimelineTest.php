<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadActivityTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Timeline Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_status_change_is_recorded_on_the_timeline(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('timeline-status');

        $statuses = LeadStatus::where('tenant_id', $tenant->id)->orderBy('sort_order')->get();
        $defaultStatus = $statuses->firstWhere('is_default', true);
        $nextStatus = $statuses->firstWhere('name_en', 'Contacted');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $defaultStatus->id]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/crm/leads/{$lead->id}", ['lead_status_id' => $nextStatus->id])
            ->assertOk();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'status_changed',
        ]);

        $timeline = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/v1/crm/leads/{$lead->id}/activities");

        $timeline->assertOk();
        $types = collect($timeline->json('data'))->pluck('type');
        $this->assertTrue($types->contains('status_changed'));
    }

    public function test_a_user_can_manually_log_a_call(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('timeline-call');

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/activities", [
                'type' => 'call',
                'description' => 'Called the client, interested in a demo next week.',
            ]);

        $response->assertCreated()->assertJsonPath('data.type', 'call');
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'call']);
    }

    public function test_only_manually_loggable_types_are_accepted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('timeline-invalid-type');

        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'lead_status_id' => $status->id]);

        Sanctum::actingAs($owner);

        // 'created' and 'assigned' are system-generated types, not
        // something a client should be able to fabricate via this endpoint.
        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/crm/leads/{$lead->id}/activities", [
                'type' => 'created',
                'description' => 'Trying to fake a system event.',
            ]);

        $response->assertStatus(422);
    }
}
