<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The full, real, end-to-end Sales flow this sprint was built for:
 * Opportunity -> Quotation -> Sales Order -> Delivery Note (stock out)
 * -> Invoice (accounting posting, no stock effect) -> Payment
 * (accounting posting) -> Sales Return + auto-generated Credit Note
 * (stock back in, accounting reversal). Every number checked below is
 * asserted against the actual database state, not just HTTP status codes.
 */
class SalesModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Sales Module Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_full_opportunity_to_paid_invoice_with_delivery_and_accounting(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-full-flow');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        // Set up: product with stock, customer, an Opportunity (CRM integration).
        $productId = $h()->postJson('/api/v1/inventory/products', [
            'sku' => 'PROD-1', 'name_en' => 'Widget', 'cost_price' => 40, 'sale_price' => 100,
        ])->json('data.id');
        $warehouseId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');
        $h()->postJson('/api/v1/inventory/stock-adjustments', [
            'product_id' => $productId, 'warehouse_id' => $warehouseId, 'type' => 'in', 'quantity' => 100,
        ]);

        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $stage = OpportunityStage::where('tenant_id', $tenant->id)->where('is_default', true)->firstOrFail();
        $opportunity = Opportunity::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'stage_id' => $stage->id]);

        // 1. Quotation linked to the Opportunity (CRM integration).
        $quotation = $h()->postJson('/api/v1/sales/quotations', [
            'customer_id' => $customer->id, 'opportunity_id' => $opportunity->id,
            'items' => [['product_id' => $productId, 'quantity' => 10, 'unit_price' => 100, 'vat_rate' => 15]],
        ]);
        $quotation->assertCreated()->assertJsonPath('data.opportunity_id', $opportunity->id);
        $this->assertEquals(1150, $quotation->json('data.total')); // 1000 + 150 VAT
        $quotationId = $quotation->json('data.id');

        $h()->patchJson("/api/v1/sales/quotations/{$quotationId}", ['status' => 'accepted'])->assertOk();
        $orderId = $h()->postJson("/api/v1/sales/quotations/{$quotationId}/convert-to-order")->json('data.id');
        $h()->patchJson("/api/v1/sales/orders/{$orderId}", ['status' => 'confirmed'])->assertOk();

        // 2. Delivery Note — the real stock-out event (NOT invoice issuance).
        $delivery = $h()->postJson("/api/v1/sales/orders/{$orderId}/deliver");
        $delivery->assertCreated()->assertJsonPath('data.status', 'draft');
        $deliveryId = $delivery->json('data.id');

        $h()->postJson("/api/v1/sales/delivery-notes/{$deliveryId}/deliver")->assertOk()->assertJsonPath('data.status', 'delivered');

        $stockAfterDelivery = $h()->getJson('/api/v1/inventory/stock-levels')->json('data.0.quantity');
        $this->assertEquals(90, $stockAfterDelivery); // 100 - 10, moved by the delivery, not the invoice

        // 3. Invoice — purely financial now. Issuing it must NOT move stock again.
        $invoiceId = $h()->postJson("/api/v1/sales/orders/{$orderId}/convert-to-invoice")->json('data.id');
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/issue")->assertOk()->assertJsonPath('data.status', 'issued');

        $stockAfterInvoice = $h()->getJson('/api/v1/inventory/stock-levels')->json('data.0.quantity');
        $this->assertEquals(90, $stockAfterInvoice, 'Issuing an invoice must not move stock a second time.');

        // Accounting integration: issuing posted a real, balanced journal entry.
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $tenant->id, 'source_type' => 'sales_invoice', 'source_id' => $invoiceId,
        ]);
        $entry = \App\Models\JournalEntry::where('source_id', $invoiceId)->firstOrFail();
        $this->assertEquals((float) $entry->totalDebit(), (float) $entry->totalCredit());
        $this->assertEquals(1150.0, (float) $entry->totalDebit());

        // 4. Payment — partial, then full, through the real CustomerPayment/allocation path.
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/record-payment", ['amount' => 500])->assertCreated();
        $this->assertDatabaseHas('customer_payments', ['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'amount' => 500]);
        $this->assertDatabaseHas('payment_allocations', ['sales_invoice_id' => $invoiceId, 'amount' => 500]);

        $invoiceAfterPartial = $h()->getJson("/api/v1/sales/invoices/{$invoiceId}")->json('data');
        $this->assertEquals('partial', $invoiceAfterPartial['status']);
        $this->assertEquals(650, $invoiceAfterPartial['balance_due']);

        // Accounting: payment posted its own balanced entry.
        $paymentEntry = \App\Models\JournalEntry::where('source_type', 'customer_payment')->firstOrFail();
        $this->assertEquals(500.0, (float) $paymentEntry->totalDebit());

        // 5. Sales Return against this invoice — auto-generates and issues a linked Credit Note.
        $return = $h()->postJson('/api/v1/sales/returns', [
            'customer_id' => $customer->id, 'sales_invoice_id' => $invoiceId,
            'items' => [['product_id' => $productId, 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 15]],
            'reason' => 'Damaged on arrival',
        ]);
        $return->assertCreated();
        $returnId = $return->json('data.id');

        $received = $h()->postJson("/api/v1/sales/returns/{$returnId}/receive");
        $received->assertOk();
        $this->assertNotNull($received->json('data.credit_note_id'));

        // Stock came back in: 90 + 2 = 92.
        $stockAfterReturn = $h()->getJson('/api/v1/inventory/stock-levels')->json('data.0.quantity');
        $this->assertEquals(92, $stockAfterReturn);

        // Credit note reduced the invoice's balance due: 2 * 100 * 1.15 = 230 credited.
        $invoiceAfterCredit = $h()->getJson("/api/v1/sales/invoices/{$invoiceId}")->json('data');
        $this->assertEquals(230, $invoiceAfterCredit['credited_amount']);
        $this->assertEquals(420, $invoiceAfterCredit['balance_due']); // 1150 - 500 - 230

        // Accounting: the credit note posted its own reversing entry.
        $this->assertDatabaseHas('journal_entries', ['source_type' => 'credit_note']);
    }

    public function test_a_delivery_note_cannot_be_delivered_twice(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-dn-twice');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P1', 'name_en' => 'P1'])->json('data.id');
        $warehouseId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');
        $h()->postJson('/api/v1/inventory/stock-adjustments', ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'type' => 'in', 'quantity' => 10]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $noteId = $h()->postJson('/api/v1/sales/delivery-notes', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ])->json('data.id');

        $h()->postJson("/api/v1/sales/delivery-notes/{$noteId}/deliver")->assertOk();
        $h()->postJson("/api/v1/sales/delivery-notes/{$noteId}/deliver")->assertStatus(422);
    }

    public function test_a_payment_can_be_allocated_across_two_invoices(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-multi-alloc');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P2', 'name_en' => 'P2'])->json('data.id');
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $invoice1 = $h()->postJson('/api/v1/sales/invoices', [
            'customer_id' => $customer->id, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 100]],
        ])->json('data.id');
        $invoice2 = $h()->postJson('/api/v1/sales/invoices', [
            'customer_id' => $customer->id, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 100]],
        ])->json('data.id');
        $h()->postJson("/api/v1/sales/invoices/{$invoice1}/issue")->assertOk();
        $h()->postJson("/api/v1/sales/invoices/{$invoice2}/issue")->assertOk();

        // A single payment of 230 (both invoices total 115 each = 230), allocated across both.
        $payment = $h()->postJson('/api/v1/sales/payments', [
            'customer_id' => $customer->id, 'amount' => 230,
            'allocations' => [
                ['sales_invoice_id' => $invoice1, 'amount' => 115],
                ['sales_invoice_id' => $invoice2, 'amount' => 115],
            ],
        ]);
        $payment->assertCreated();
        $this->assertEquals(230, $payment->json('data.allocated_amount'));
        $this->assertEquals(0, $payment->json('data.unallocated_amount'));

        $this->assertEquals('paid', $h()->getJson("/api/v1/sales/invoices/{$invoice1}")->json('data.status'));
        $this->assertEquals('paid', $h()->getJson("/api/v1/sales/invoices/{$invoice2}")->json('data.status'));
    }

    public function test_a_credit_note_cannot_exceed_the_invoice_balance(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-cn-limit');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P3', 'name_en' => 'P3'])->json('data.id');
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $invoiceId = $h()->postJson('/api/v1/sales/invoices', [
            'customer_id' => $customer->id, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/issue")->assertOk();

        // Invoice total is 115; a credit note for 200 worth of goods must be rejected.
        $response = $h()->postJson('/api/v1/sales/credit-notes', [
            'sales_invoice_id' => $invoiceId,
            'items' => [['product_id' => $productId, 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 15]],
        ]);

        $response->assertStatus(422);
    }
}
