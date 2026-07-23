<?php

namespace Tests\Feature\Tenancy;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmOpportunityTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Opportunity Isolation Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_opportunities_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('opp-iso-a');
        [$tenantB] = $this->registerTenant('opp-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        $stageB = OpportunityStage::where('tenant_id', $tenantB->id)->where('is_default', true)->firstOrFail();
        $customerB = Customer::create([
            'tenant_id' => $tenantB->id, 'customer_number' => 'CU-888888', 'first_name' => 'Hidden Customer',
        ]);
        Opportunity::create([
            'tenant_id' => $tenantB->id,
            'opportunity_number' => 'OP-888888',
            'name' => 'Confidential Deal',
            'customer_id' => $customerB->id,
            'stage_id' => $stageB->id,
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('opportunities')->where('name', 'Confidential Deal')->get();

        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_opportunity_number_sequences_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('opp-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('opp-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $customerA = Customer::factory()->create(['tenant_id' => $tenantA->id]);
        $oppA = app(\App\Services\OpportunityService::class)->create($ownerA, [
            'name' => 'Deal A', 'customer_id' => $customerA->id,
        ]);

        $context->set($tenantB);
        $context->apply();
        $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        $oppB = app(\App\Services\OpportunityService::class)->create($ownerB, [
            'name' => 'Deal B', 'customer_id' => $customerB->id,
        ]);

        $context->reset();

        $this->assertEquals('OP-000001', $oppA->opportunity_number);
        $this->assertEquals('OP-000001', $oppB->opportunity_number);
    }
}
