<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsReceiptService
{
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $receipts,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
    ) {}

    public function create(User $actor, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($actor, $data) {
            $receipt = $this->receipts->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'goods_receipt_number', 'GRN'),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? $this->inventory->defaultWarehouseFor($actor->tenant)?->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                GoodsReceiptItem::create([
                    'tenant_id' => $receipt->tenant_id,
                    'goods_receipt_id' => $receipt->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                ]);
            }

            return $receipt;
        });
    }

    /**
     * Real Inventory-side counterpart to Purchase's PO receiving flow —
     * PurchaseOrderService::receive() now calls this instead of moving
     * stock directly, the same physical/financial split the Sales
     * sprint established (Delivery Notes vs. Invoices).
     */
    public function createFromPurchaseOrder(User $actor, PurchaseOrder $order): GoodsReceipt
    {
        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
        ])->all();

        return $this->create($actor, [
            'purchase_order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'items' => $items,
            'notes' => $order->notes,
        ]);
    }

    /** The real warehouse event — moves every line item's stock IN. Rejects double-receiving. */
    public function receive(User $actor, GoodsReceipt $receipt): GoodsReceipt
    {
        if ($receipt->status !== 'draft') {
            throw new InvalidArgumentException("Goods receipt {$receipt->document_number} has already been received.");
        }

        $warehouse = $receipt->warehouse ?? $this->inventory->defaultWarehouseFor($actor->tenant);

        if (! $warehouse) {
            throw new InvalidArgumentException('No warehouse is configured for this receipt.');
        }

        return DB::transaction(function () use ($actor, $receipt, $warehouse) {
            foreach ($receipt->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_IN, (float) $item->quantity,
                    'goods_receipt', $receipt->id, "Received via {$receipt->document_number}"
                );
            }

            return $this->receipts->update($receipt, ['status' => 'received', 'updated_by_user_id' => $actor->id]);
        });
    }
}
