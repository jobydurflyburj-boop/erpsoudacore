<?php

namespace Tests\Feature\Inventory;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryMvpTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Inventory Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_registration_provisions_a_default_warehouse(): void
    {
        [$tenant] = $this->registerTenantWithOwner('inv-provision');
        $this->assertDatabaseHas('warehouses', ['tenant_id' => $tenant->id, 'is_default' => true]);
    }

    public function test_full_product_and_stock_flow(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-flow');
        Sanctum::actingAs($owner);

        $product = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/inventory/products', [
            'sku' => 'WIDGET-001', 'name_en' => 'Widget', 'cost_price' => 10, 'sale_price' => 20, 'reorder_point' => 5,
        ]);
        $product->assertCreated();
        $productId = $product->json('data.id');

        $warehouse = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/inventory/warehouses');
        $warehouseId = $warehouse->json('data.0.id');

        $adjust = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/inventory/stock-adjustments', [
            'product_id' => $productId, 'warehouse_id' => $warehouseId, 'type' => 'in', 'quantity' => 100,
        ]);
        $adjust->assertCreated();

        $levels = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/inventory/stock-levels');
        $levels->assertOk();
        $this->assertEquals(100, $levels->json('data.0.quantity'));
    }

    public function test_stock_cannot_go_negative(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-negative');
        Sanctum::actingAs($owner);

        $product = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/inventory/products', ['sku' => 'W2', 'name_en' => 'W2'])
            ->json('data.id');
        $warehouseId = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/v1/inventory/warehouses')->json('data.0.id');

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/inventory/stock-adjustments', [
            'product_id' => $product, 'warehouse_id' => $warehouseId, 'type' => 'out', 'quantity' => 5,
        ]);

        $response->assertStatus(422);
    }
}
