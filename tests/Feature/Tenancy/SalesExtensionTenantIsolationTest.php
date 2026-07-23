<?php

namespace Tests\Feature\Tenancy;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Sales Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_customer_payments_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('sales-ext-iso-a');
        [$tenantB] = $this->registerTenant('sales-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        $customerB = Customer::create(['tenant_id' => $tenantB->id, 'customer_number' => 'CU-777777', 'first_name' => 'Hidden']);
        CustomerPayment::create([
            'tenant_id' => $tenantB->id, 'payment_number' => 'PMT-777777', 'customer_id' => $customerB->id,
            'amount' => 999, 'payment_date' => now()->toDateString(),
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('customer_payments')->where('payment_number', 'PMT-777777')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_journal_entries_from_sales_are_still_tenant_isolated(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('sales-je-iso-a');
        [$tenantB, $ownerB] = $this->registerTenant('sales-je-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        $productB = \App\Models\Product::factory()->create(['tenant_id' => $tenantB->id]);
        $invoiceB = app(\App\Services\SalesInvoiceService::class)->create($ownerB, [
            'customer_id' => $customerB->id,
            'items' => [['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 15]],
        ]);
        app(\App\Services\SalesInvoiceService::class)->issue($ownerB, $invoiceB);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('journal_entries')->where('source_id', $invoiceB->id)->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }
}
