<?php

namespace Tests\Feature\Purchase;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseMvpTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Purchase Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_receiving_a_purchase_order_increases_stock(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('po-receive');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'PART-1', 'name_en' => 'Part'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'Acme Supplies'])->json('data.id');

        $po = $h()->postJson('/api/v1/purchase/orders', [
            'supplier_id' => $supplierId,
            'items' => [['product_id' => $productId, 'quantity' => 50, 'unit_cost' => 8]],
        ]);
        $po->assertCreated();
        $this->assertEquals(400, $po->json('data.subtotal')); // 50 * 8
        $poId = $po->json('data.id');

        $receive = $h()->postJson("/api/v1/purchase/orders/{$poId}/receive");
        $receive->assertOk()->assertJsonPath('data.status', 'received');

        $levels = $h()->getJson('/api/v1/inventory/stock-levels');
        $this->assertEquals(50, $levels->json('data.0.quantity'));

        // Receiving twice is rejected.
        $h()->postJson("/api/v1/purchase/orders/{$poId}/receive")->assertStatus(422);
    }

    public function test_receiving_a_purchase_order_creates_a_real_linked_goods_receipt(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('po-receive-grn-link');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'PART-2', 'name_en' => 'Part 2'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'Acme Supplies 2'])->json('data.id');

        $poId = $h()->postJson('/api/v1/purchase/orders', [
            'supplier_id' => $supplierId,
            'items' => [['product_id' => $productId, 'quantity' => 20, 'unit_cost' => 5]],
        ])->json('data.id');

        $h()->postJson("/api/v1/purchase/orders/{$poId}/receive")->assertOk();

        // The Inventory-side warehouse event — a real Goods Receipt, not a direct stock move from Purchase.
        $this->assertDatabaseHas('goods_receipts', [
            'tenant_id' => $tenant->id, 'purchase_order_id' => $poId, 'supplier_id' => $supplierId, 'status' => 'received',
        ]);
        $this->assertDatabaseHas('stock_movements', ['tenant_id' => $tenant->id, 'reference_type' => 'goods_receipt']);
    }
}
