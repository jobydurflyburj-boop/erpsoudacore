<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesMvpTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Sales Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_full_quotation_to_paid_invoice_flow_with_correct_vat(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-flow');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'ITEM-1', 'name_en' => 'Item'])->json('data.id');
        $warehouseId = $h()->getJson('/api/v1/inventory/warehouses')->json('data.0.id');
        $h()->postJson('/api/v1/inventory/stock-adjustments', [
            'product_id' => $productId, 'warehouse_id' => $warehouseId, 'type' => 'in', 'quantity' => 20,
        ]);

        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $quotation = $h()->postJson('/api/v1/sales/quotations', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $productId, 'quantity' => 10, 'unit_price' => 100, 'vat_rate' => 15]],
        ]);
        $quotation->assertCreated();
        // subtotal 1000, vat 150, total 1150
        $this->assertEquals(1000, $quotation->json('data.subtotal'));
        $this->assertEquals(150, $quotation->json('data.vat_amount'));
        $this->assertEquals(1150, $quotation->json('data.total'));
        $quotationId = $quotation->json('data.id');

        $h()->patchJson("/api/v1/sales/quotations/{$quotationId}", ['status' => 'accepted'])->assertOk();

        $order = $h()->postJson("/api/v1/sales/quotations/{$quotationId}/convert-to-order");
        $order->assertCreated()->assertJsonPath('data.total', 1150);
        $orderId = $order->json('data.id');

        $h()->patchJson("/api/v1/sales/orders/{$orderId}", ['status' => 'confirmed'])->assertOk();

        $invoice = $h()->postJson("/api/v1/sales/orders/{$orderId}/convert-to-invoice");
        $invoice->assertCreated()->assertJsonPath('data.total', 1150);
        $invoiceId = $invoice->json('data.id');

        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/issue")->assertOk();

        // Issuing moves stock OUT — 20 - 10 = 10 remaining.
        $levels = $h()->getJson('/api/v1/inventory/stock-levels');
        $this->assertEquals(10, $levels->json('data.0.quantity'));

        $partial = $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/record-payment", ['amount' => 500]);
        $partial->assertOk()->assertJsonPath('data.status', 'partial')->assertJsonPath('data.balance_due', 650);

        $final = $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/record-payment", ['amount' => 650]);
        $final->assertOk()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.balance_due', 0);

        // Overpayment is rejected.
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/record-payment", ['amount' => 10])->assertStatus(422);
    }

    public function test_a_pending_quotation_cannot_be_converted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('sales-pending');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'X1', 'name_en' => 'X1'])->json('data.id');
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $quotationId = $h()->postJson('/api/v1/sales/quotations', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 50]],
        ])->json('data.id');

        $h()->postJson("/api/v1/sales/quotations/{$quotationId}/convert-to-order")->assertStatus(422);
    }
}
