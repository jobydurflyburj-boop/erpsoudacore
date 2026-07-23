<?php

namespace Tests\Feature\Tenancy;

use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Purchase Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_supplier_bills_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('purchase-ext-iso-a');
        [$tenantB] = $this->registerTenant('purchase-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        $supplierB = Supplier::create(['tenant_id' => $tenantB->id, 'supplier_number' => 'SUP-777777', 'name' => 'Hidden Supplier']);
        SupplierBill::create([
            'tenant_id' => $tenantB->id, 'document_number' => 'BILL-777777', 'supplier_id' => $supplierB->id,
            'document_date' => now()->toDateString(), 'total' => 999,
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('supplier_bills')->where('document_number', 'BILL-777777')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_supplier_bill_numbers_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('purchase-bill-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('purchase-bill-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $supplierA = Supplier::factory()->create(['tenant_id' => $tenantA->id]);
        $productA = \App\Models\Product::factory()->create(['tenant_id' => $tenantA->id]);
        $billA = app(\App\Services\SupplierBillService::class)->create($ownerA, [
            'supplier_id' => $supplierA->id, 'items' => [['product_id' => $productA->id, 'quantity' => 1, 'unit_cost' => 10]],
        ]);

        $context->set($tenantB);
        $context->apply();
        $supplierB = Supplier::factory()->create(['tenant_id' => $tenantB->id]);
        $productB = \App\Models\Product::factory()->create(['tenant_id' => $tenantB->id]);
        $billB = app(\App\Services\SupplierBillService::class)->create($ownerB, [
            'supplier_id' => $supplierB->id, 'items' => [['product_id' => $productB->id, 'quantity' => 1, 'unit_cost' => 10]],
        ]);

        $context->reset();

        $this->assertEquals('BILL-000001', $billA->document_number);
        $this->assertEquals('BILL-000001', $billB->document_number);
    }
}
