<?php

namespace Tests\Feature\Tenancy;

use App\Models\Customer;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmCustomerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Customer Isolation Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_customers_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('cust-iso-a');
        [$tenantB] = $this->registerTenant('cust-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        Customer::create([
            'tenant_id' => $tenantB->id,
            'customer_number' => 'CU-999999',
            'first_name' => 'Confidential Customer',
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('customers')->where('first_name', 'Confidential Customer')->get();

        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_customer_number_sequences_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('cust-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('cust-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $customerA = app(\App\Services\CustomerService::class)->create($ownerA, ['first_name' => 'A1']);

        $context->set($tenantB);
        $context->apply();
        $customerB = app(\App\Services\CustomerService::class)->create($ownerB, ['first_name' => 'B1']);

        $context->reset();

        $this->assertEquals('CU-000001', $customerA->customer_number);
        $this->assertEquals('CU-000001', $customerB->customer_number);
    }
}
