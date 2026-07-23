<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly SequenceService $sequences,
        private readonly GoodsReceiptService $goodsReceipts,
    ) {}

    public function create(User $actor, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($actor, $data) {
            $totals = $this->calculateTotals($data['items'], 'unit_cost');

            $po = $this->purchaseOrders->create([
                'tenant_id' => $actor->tenant_id,
                'po_number' => $this->sequences->next($actor->tenant_id, 'po_number', 'PO'),
                'supplier_id' => $data['supplier_id'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceItems($po, $totals['lines']);

            return $po;
        });
    }

    public function update(User $actor, PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($actor, $po, $data) {
            $updates = ['updated_by_user_id' => $actor->id];

            if (array_key_exists('items', $data)) {
                $totals = $this->calculateTotals($data['items'], 'unit_cost');
                $updates = array_merge($updates, [
                    'subtotal' => $totals['subtotal'], 'vat_amount' => $totals['vat_amount'], 'total' => $totals['total'],
                ]);
                $po->items()->delete();
                $this->replaceItems($po, $totals['lines']);
            }

            foreach (['supplier_id', 'order_date', 'notes', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            $po = $this->purchaseOrders->update($po, $updates);

            return $po->fresh('items');
        });
    }

    /**
     * Receiving a PO now creates and receives a real Goods Receipt —
     * the Inventory-side warehouse event — rather than moving stock
     * directly, the same physical/financial separation the Sales
     * sprint established for Delivery Notes vs. Invoices. Only a
     * not-yet-received PO can be received, and only once.
     */
    public function receive(User $actor, PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status === PurchaseOrder::STATUS_RECEIVED) {
            throw new InvalidArgumentException("Purchase order {$po->po_number} has already been received.");
        }

        return DB::transaction(function () use ($actor, $po) {
            $receipt = $this->goodsReceipts->createFromPurchaseOrder($actor, $po->load('items'));
            $this->goodsReceipts->receive($actor, $receipt);

            return $this->purchaseOrders->update($po, ['status' => PurchaseOrder::STATUS_RECEIVED, 'updated_by_user_id' => $actor->id]);
        });
    }

    /** The Goods Receipt created for this PO's most recent receiving, if it has been received — useful for the frontend to link straight to the warehouse-side document. */
    public function goodsReceiptFor(PurchaseOrder $po): ?GoodsReceipt
    {
        return GoodsReceipt::where('purchase_order_id', $po->id)->latest('created_at')->first();
    }

    private function replaceItems(PurchaseOrder $po, array $lines): void
    {
        foreach ($lines as $line) {
            PurchaseOrderItem::create([
                'tenant_id' => $po->tenant_id,
                'purchase_order_id' => $po->id,
                'product_id' => $line['product_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'],
                'unit_cost' => $line['unit_cost'],
                'line_total' => $line['line_total'],
            ]);
        }
    }
}
