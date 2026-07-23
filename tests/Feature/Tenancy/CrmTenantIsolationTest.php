<?php

namespace Tests\Feature\Tenancy;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Isolation Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_leads_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('crm-iso-a');
        [$tenantB] = $this->registerTenant('crm-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        $statusB = LeadStatus::where('tenant_id', $tenantB->id)->where('is_default', true)->firstOrFail();
        Lead::create([
            'tenant_id' => $tenantB->id,
            'lead_status_id' => $statusB->id,
            'first_name' => 'Confidential',
            'lead_number' => 'LD-999999',
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('leads')->where('first_name', 'Confidential')->get();

        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_lead_sequence_numbers_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('crm-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('crm-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $leadA = app(\App\Services\LeadService::class)->create($ownerA, ['first_name' => 'A1']);

        $context->set($tenantB);
        $context->apply();
        $leadB = app(\App\Services\LeadService::class)->create($ownerB, ['first_name' => 'B1']);

        $context->reset();

        // Both tenants' first lead gets LD-000001 — the counter is scoped
        // per-tenant, not global (sequence_counters.tenant_id, unique
        // together with name).
        $this->assertEquals('LD-000001', $leadA->lead_number);
        $this->assertEquals('LD-000001', $leadB->lead_number);
    }
}
