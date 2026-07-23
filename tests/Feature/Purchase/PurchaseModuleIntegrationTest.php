<?php

namespace Tests\Feature\Purchase;

use App\Models\JournalEntry;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The full, real Purchase flow this sprint was built for: PO -> Goods
 * Receipt (real stock-in, from the Inventory sprint) -> Supplier Bill
 * (the real liability event, posts to Accounts Payable) -> Supplier
 * Payment (posts Dr AP / Cr Cash, real multi-bill allocation) ->
 * Purchase Return + auto-generated Debit Note (stock back out,
 * accounting reversal). Every number is asserted against actual
 * database state, mirroring SalesModuleIntegrationTest exactly.
 */
class PurchaseModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Purchase Module Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_full_po_to_paid_bill_with_return_and_debit_note(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('purchase-full-flow');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'PART-X', 'name_en' => 'Part X'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'Acme Supplies'])->json('data.id');

        // 1. Purchase Order -> receive (creates + receives a real Goods Receipt).
        $poId = $h()->postJson('/api/v1/purchase/orders', [
            'supplier_id' => $supplierId,
            'items' => [['product_id' => $productId, 'quantity' => 100, 'unit_cost' => 10]],
        ])->json('data.id');

        $h()->postJson("/api/v1/purchase/orders/{$poId}/receive")->assertOk();

        $goodsReceipt = \App\Models\GoodsReceipt::where('purchase_order_id', $poId)->firstOrFail();
        $stock = $h()->getJson('/api/v1/inventory/stock-levels')->json('data.0.quantity');
        $this->assertEquals(100, $stock);

        // 2. Supplier Bill created FROM the Goods Receipt — copies quantities/costs actually received.
        $bill = $h()->postJson("/api/v1/purchase/goods-receipts/{$goodsReceipt->id}/bill");
        $bill->assertCreated();
        $this->assertEquals(1000, $bill->json('data.subtotal'));
        $this->assertEquals(150, $bill->json('data.vat_amount'));
        $this->assertEquals(1150, $bill->json('data.total'));
        $billId = $bill->json('data.id');

        // Approving posts to Accounts Payable.
        $h()->postJson("/api/v1/purchase/bills/{$billId}/approve")->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('journal_entries', ['tenant_id' => $tenant->id, 'source_type' => 'supplier_bill', 'source_id' => $billId]);
        $billEntry = JournalEntry::where('source_id', $billId)->firstOrFail();
        $this->assertEquals(1150.0, (float) $billEntry->totalDebit());
        $this->assertEquals((float) $billEntry->totalDebit(), (float) $billEntry->totalCredit());

        // 3. Supplier Payment — partial then full, through the real allocation path.
        $h()->postJson("/api/v1/purchase/bills/{$billId}/record-payment", ['amount' => 500])->assertCreated();
        $this->assertDatabaseHas('supplier_payments', ['tenant_id' => $tenant->id, 'supplier_id' => $supplierId, 'amount' => 500]);

        $billAfterPartial = $h()->getJson("/api/v1/purchase/bills/{$billId}")->json('data');
        $this->assertEquals('partial', $billAfterPartial['status']);
        $this->assertEquals(650, $billAfterPartial['balance_due']);

        $paymentEntry = JournalEntry::where('source_type', 'supplier_payment')->firstOrFail();
        $this->assertEquals(500.0, (float) $paymentEntry->totalDebit());

        // 4. Purchase Return against this bill — auto-generates and issues a linked Debit Note.
        $return = $h()->postJson('/api/v1/purchase/returns', [
            'supplier_id' => $supplierId, 'goods_receipt_id' => $goodsReceipt->id,
            'items' => [['product_id' => $productId, 'quantity' => 5, 'unit_price' => 10, 'vat_rate' => 15]],
            'reason' => 'Wrong specification',
        ]);
        $return->assertCreated();
        $returnId = $return->json('data.id');

        $returned = $h()->postJson("/api/v1/purchase/returns/{$returnId}/return", ['supplier_bill_id' => $billId]);
        $returned->assertOk();
        $this->assertNotNull($returned->json('data.debit_note_id'));

        // Stock went back out: 100 - 5 = 95.
        $stockAfterReturn = $h()->getJson('/api/v1/inventory/stock-levels')->json('data.0.quantity');
        $this->assertEquals(95, $stockAfterReturn);

        // Debit note reduced the bill's balance due: 5 * 10 * 1.15 = 57.50 credited.
        $billAfterDebit = $h()->getJson("/api/v1/purchase/bills/{$billId}")->json('data');
        $this->assertEquals(57.5, $billAfterDebit['credited_amount']);
        $this->assertEquals(592.5, $billAfterDebit['balance_due']); // 1150 - 500 - 57.50

        $this->assertDatabaseHas('journal_entries', ['source_type' => 'debit_note']);
    }

    public function test_a_debit_note_cannot_exceed_the_bill_balance(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('purchase-dn-limit');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P1', 'name_en' => 'P1'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'S1'])->json('data.id');

        $billId = $h()->postJson('/api/v1/purchase/bills', [
            'supplier_id' => $supplierId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_cost' => 100, 'vat_rate' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/purchase/bills/{$billId}/approve")->assertOk();

        $response = $h()->postJson('/api/v1/purchase/debit-notes', [
            'supplier_bill_id' => $billId,
            'items' => [['product_id' => $productId, 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 15]],
        ]);

        $response->assertStatus(422);
    }

    public function test_a_payment_can_be_allocated_across_two_bills(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('purchase-multi-alloc');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P2', 'name_en' => 'P2'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'S2'])->json('data.id');

        $bill1 = $h()->postJson('/api/v1/purchase/bills', [
            'supplier_id' => $supplierId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_cost' => 100]],
        ])->json('data.id');
        $bill2 = $h()->postJson('/api/v1/purchase/bills', [
            'supplier_id' => $supplierId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_cost' => 100]],
        ])->json('data.id');
        $h()->postJson("/api/v1/purchase/bills/{$bill1}/approve")->assertOk();
        $h()->postJson("/api/v1/purchase/bills/{$bill2}/approve")->assertOk();

        $payment = $h()->postJson('/api/v1/purchase/payments', [
            'supplier_id' => $supplierId, 'amount' => 230,
            'allocations' => [
                ['supplier_bill_id' => $bill1, 'amount' => 115],
                ['supplier_bill_id' => $bill2, 'amount' => 115],
            ],
        ]);
        $payment->assertCreated();
        $this->assertEquals(0, $payment->json('data.unallocated_amount'));

        $this->assertEquals('paid', $h()->getJson("/api/v1/purchase/bills/{$bill1}")->json('data.status'));
        $this->assertEquals('paid', $h()->getJson("/api/v1/purchase/bills/{$bill2}")->json('data.status'));
    }

    public function test_a_supplier_bill_cannot_be_approved_twice(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('purchase-bill-twice');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P3', 'name_en' => 'P3'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'S3'])->json('data.id');

        $billId = $h()->postJson('/api/v1/purchase/bills', [
            'supplier_id' => $supplierId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_cost' => 50]],
        ])->json('data.id');

        $h()->postJson("/api/v1/purchase/bills/{$billId}/approve")->assertOk();
        $h()->postJson("/api/v1/purchase/bills/{$billId}/approve")->assertStatus(422);
    }
}
