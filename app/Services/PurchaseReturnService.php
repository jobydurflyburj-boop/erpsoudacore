<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\User;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseReturnService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $returns,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
        private readonly DebitNoteService $debitNoteService,
    ) {}

    public function create(User $actor, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($actor, $data) {
            $return = $this->returns->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'purchase_return_number', 'PR'),
                'supplier_id' => $data['supplier_id'],
                'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? $this->inventory->defaultWarehouseFor($actor->tenant)?->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseReturnItem::create([
                    'tenant_id' => $return->tenant_id,
                    'purchase_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? 15.00,
                ]);
            }

            return $return;
        });
    }

    /**
     * Returning is the real warehouse event — stock moves OUT back to
     * the supplier. If a supplier_bill_id is provided (the bill this
     * return relates to), this also generates and issues a matching
     * Debit Note automatically, the same reasoning SalesReturnService
     * applies for Credit Notes: a physical return should always get a
     * financial counterpart, not depend on a manual follow-up step.
     */
    public function returnGoods(User $actor, PurchaseReturn $return, ?string $supplierBillId = null): PurchaseReturn
    {
        if ($return->status !== 'draft') {
            throw new InvalidArgumentException("Purchase return {$return->document_number} has already been returned.");
        }

        $warehouse = $return->warehouse ?? $this->inventory->defaultWarehouseFor($actor->tenant);

        if (! $warehouse) {
            throw new InvalidArgumentException('No warehouse is configured for this return.');
        }

        return DB::transaction(function () use ($actor, $return, $warehouse, $supplierBillId) {
            foreach ($return->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_OUT, (float) $item->quantity,
                    'purchase_return', $return->id, "Returned to supplier via {$return->document_number}"
                );
            }

            $updates = ['status' => 'returned', 'updated_by_user_id' => $actor->id];

            if ($supplierBillId) {
                $bill = SupplierBill::findOrFail($supplierBillId);
                $creditableItems = $return->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'vat_rate' => (float) $item->vat_rate,
                ])->all();

                $debitNote = $this->debitNoteService->create($actor, [
                    'supplier_bill_id' => $bill->id,
                    'items' => $creditableItems,
                    'reason' => "Auto-generated from purchase return {$return->document_number}: ".($return->reason ?? ''),
                ]);
                $debitNote = $this->debitNoteService->issue($actor, $debitNote);

                $updates['debit_note_id'] = $debitNote->id;
            }

            return $this->returns->update($return, $updates);
        });
    }
}
