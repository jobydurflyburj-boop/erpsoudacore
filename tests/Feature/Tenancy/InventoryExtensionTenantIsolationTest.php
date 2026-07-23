<?php

namespace Tests\Feature\Tenancy;

use App\Models\ProductCategory;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Inventory Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_product_categories_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('inv-ext-iso-a');
        [$tenantB] = $this->registerTenant('inv-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        ProductCategory::create(['tenant_id' => $tenantB->id, 'name_en' => 'Confidential Category']);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('product_categories')->where('name_en', 'Confidential Category')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_goods_receipt_numbers_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('inv-grn-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('inv-grn-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $productA = \App\Models\Product::factory()->create(['tenant_id' => $tenantA->id]);
        $warehouseA = app(\App\Services\InventoryService::class)->defaultWarehouseFor($tenantA);
        $receiptA = app(\App\Services\GoodsReceiptService::class)->create($ownerA, [
            'warehouse_id' => $warehouseA->id, 'items' => [['product_id' => $productA->id, 'quantity' => 1]],
        ]);

        $context->set($tenantB);
        $context->apply();
        $productB = \App\Models\Product::factory()->create(['tenant_id' => $tenantB->id]);
        $warehouseB = app(\App\Services\InventoryService::class)->defaultWarehouseFor($tenantB);
        $receiptB = app(\App\Services\GoodsReceiptService::class)->create($ownerB, [
            'warehouse_id' => $warehouseB->id, 'items' => [['product_id' => $productB->id, 'quantity' => 1]],
        ]);

        $context->reset();

        $this->assertEquals('GRN-000001', $receiptA->document_number);
        $this->assertEquals('GRN-000001', $receiptB->document_number);
    }
}
