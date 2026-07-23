<?php

namespace Tests\Feature\Inventory;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The full, real Inventory flow this sprint was built for: taxonomy
 * (Category/Unit/Brand/Barcode) -> a second Warehouse -> Goods Receipt
 * (real stock-in, replacing PurchaseOrderService's old direct movement)
 * -> Stock Transfer between warehouses -> Stock Adjustment (with real
 * accounting posting) -> Goods Issue (with real accounting posting) ->
 * Low Stock Alert firing. Every number is asserted against actual
 * database state.
 */
class InventoryModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Inventory Module Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_full_taxonomy_receipt_transfer_adjustment_issue_flow(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-full-flow');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        // 1. Taxonomy: Category, Unit, Brand — real entities now.
        $categoryId = $h()->postJson('/api/v1/inventory/categories', ['name_en' => 'Electronics'])->json('data.id');
        $unitId = $h()->postJson('/api/v1/inventory/units', ['code' => 'PCS', 'name_en' => 'Pieces'])->json('data.id');
        $brandId = $h()->postJson('/api/v1/inventory/brands', ['name' => 'Acme'])->json('data.id');

        // 2. Product with barcode + full taxonomy.
        $product = $h()->postJson('/api/v1/inventory/products', [
            'sku' => 'WIDGET-1', 'barcode' => '6291041500213', 'name_en' => 'Widget',
            'category_id' => $categoryId, 'unit_id' => $unitId, 'brand_id' => $brandId,
            'cost_price' => 50, 'sale_price' => 100, 'reorder_point' => 10,
        ]);
        $product->assertCreated();
        $productId = $product->json('data.id');

        // Barcode lookup — real Barcode Support.
        $lookup = $h()->getJson('/api/v1/inventory/products-by-barcode?barcode=6291041500213');
        $lookup->assertOk()->assertJsonPath('data.id', $productId);

        $mainWarehouseId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');
        $secondWarehouse = $h()->postJson('/api/v1/inventory/warehouses', ['name' => 'Secondary Warehouse']);
        $secondWarehouse->assertCreated();
        $secondWarehouseId = $secondWarehouse->json('data.id');

        // 3. Goods Receipt — the real stock-in event (standalone, no PO).
        $receipt = $h()->postJson('/api/v1/inventory/goods-receipts', [
            'warehouse_id' => $mainWarehouseId,
            'items' => [['product_id' => $productId, 'quantity' => 100, 'unit_cost' => 50]],
        ]);
        $receipt->assertCreated();
        $receiptId = $receipt->json('data.id');

        $h()->postJson("/api/v1/inventory/goods-receipts/{$receiptId}/receive")->assertOk()->assertJsonPath('data.status', 'received');

        $levelsAfterReceipt = $h()->getJson('/api/v1/inventory/stock-levels')->json('data');
        $this->assertEquals(100, collect($levelsAfterReceipt)->firstWhere('warehouse.id', $mainWarehouseId)['quantity']);

        // 4. Stock Transfer — 30 units from Main to Secondary.
        $transfer = $h()->postJson('/api/v1/inventory/transfers', [
            'from_warehouse_id' => $mainWarehouseId, 'to_warehouse_id' => $secondWarehouseId,
            'items' => [['product_id' => $productId, 'quantity' => 30]],
        ]);
        $transfer->assertCreated();
        $transferId = $transfer->json('data.id');

        $h()->postJson("/api/v1/inventory/transfers/{$transferId}/complete")->assertOk()->assertJsonPath('data.status', 'completed');

        $levelsAfterTransfer = collect($h()->getJson('/api/v1/inventory/stock-levels')->json('data'));
        $this->assertEquals(70, $levelsAfterTransfer->firstWhere('warehouse.id', $mainWarehouseId)['quantity']);
        $this->assertEquals(30, $levelsAfterTransfer->firstWhere('warehouse.id', $secondWarehouseId)['quantity']);

        // 5. Stock Adjustment — a shrinkage write-off of 5 units at Main, posts real accounting.
        $adjustment = $h()->postJson('/api/v1/inventory/adjustments', [
            'warehouse_id' => $mainWarehouseId,
            'items' => [['product_id' => $productId, 'quantity_change' => -5, 'reason' => 'Damaged in storage']],
            'reason' => 'Monthly stock count correction',
        ]);
        $adjustment->assertCreated();
        $adjustmentId = $adjustment->json('data.id');

        $h()->postJson("/api/v1/inventory/adjustments/{$adjustmentId}/approve")->assertOk()->assertJsonPath('data.status', 'approved');

        $levelsAfterAdjustment = collect($h()->getJson('/api/v1/inventory/stock-levels')->json('data'));
        $this->assertEquals(65, $levelsAfterAdjustment->firstWhere('warehouse.id', $mainWarehouseId)['quantity']);

        // Accounting: 5 units * 50 cost = 250 SAR write-off, Dr Expense / Cr Inventory.
        $this->assertDatabaseHas('journal_entries', ['tenant_id' => $tenant->id, 'source_type' => 'stock_adjustment', 'source_id' => $adjustmentId]);
        $adjEntry = JournalEntry::where('source_id', $adjustmentId)->firstOrFail();
        $this->assertEquals(250.0, (float) $adjEntry->totalDebit());
        $this->assertEquals((float) $adjEntry->totalDebit(), (float) $adjEntry->totalCredit());

        // 6. Goods Issue — 10 units issued for internal use from Secondary, posts real accounting.
        $issue = $h()->postJson('/api/v1/inventory/goods-issues', [
            'warehouse_id' => $secondWarehouseId, 'issued_to' => 'Marketing Dept',
            'items' => [['product_id' => $productId, 'quantity' => 10]],
        ]);
        $issue->assertCreated();
        $issueId = $issue->json('data.id');

        $h()->postJson("/api/v1/inventory/goods-issues/{$issueId}/issue")->assertOk()->assertJsonPath('data.status', 'issued');

        $levelsAfterIssue = collect($h()->getJson('/api/v1/inventory/stock-levels')->json('data'));
        $this->assertEquals(20, $levelsAfterIssue->firstWhere('warehouse.id', $secondWarehouseId)['quantity']);

        $issueEntry = JournalEntry::where('source_id', $issueId)->firstOrFail();
        $this->assertEquals(500.0, (float) $issueEntry->totalDebit()); // 10 * 50

        // 7. Low Stock Alert — total stock is now 65+20=85, still above reorder point 10, so NOT low yet.
        $lowStock = $h()->getJson('/api/v1/inventory/low-stock');
        $lowStock->assertOk();
        $this->assertFalse(collect($lowStock->json('data'))->contains('id', $productId));

        // Push it below reorder point via a large goods issue, confirm the product now appears.
        $bigIssue = $h()->postJson('/api/v1/inventory/goods-issues', [
            'warehouse_id' => $secondWarehouseId,
            'items' => [['product_id' => $productId, 'quantity' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/inventory/goods-issues/{$bigIssue}/issue")->assertOk();

        $lowStockAfter = $h()->getJson('/api/v1/inventory/low-stock');
        $this->assertTrue(collect($lowStockAfter->json('data'))->contains('id', $productId));

        $this->assertDatabaseHas('notifications', ['tenant_id' => $tenant->id, 'category' => 'inventory.low_stock']);
    }

    public function test_a_stock_transfer_to_the_same_warehouse_is_rejected(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-transfer-same');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P1', 'name_en' => 'P1'])->json('data.id');
        $warehouseId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');

        $response = $h()->postJson('/api/v1/inventory/transfers', [
            'from_warehouse_id' => $warehouseId, 'to_warehouse_id' => $warehouseId,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_a_stock_transfer_cannot_be_completed_twice(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-transfer-twice');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P2', 'name_en' => 'P2'])->json('data.id');
        $mainId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');
        $secondId = $h()->postJson('/api/v1/inventory/warehouses', ['name' => 'W2'])->json('data.id');
        $h()->postJson('/api/v1/inventory/stock-adjustments', ['product_id' => $productId, 'warehouse_id' => $mainId, 'type' => 'in', 'quantity' => 10]);

        $transferId = $h()->postJson('/api/v1/inventory/transfers', [
            'from_warehouse_id' => $mainId, 'to_warehouse_id' => $secondId,
            'items' => [['product_id' => $productId, 'quantity' => 5]],
        ])->json('data.id');

        $h()->postJson("/api/v1/inventory/transfers/{$transferId}/complete")->assertOk();
        $h()->postJson("/api/v1/inventory/transfers/{$transferId}/complete")->assertStatus(422);
    }

    public function test_the_default_warehouse_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('inv-wh-default-delete');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $defaultId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');

        $h()->deleteJson("/api/v1/inventory/warehouses/{$defaultId}")->assertStatus(409);
    }
}
