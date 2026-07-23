<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesReturnService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $returns,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
        private readonly CreditNoteService $creditNoteService,
    ) {}

    public function create(User $actor, array $data): SalesReturn
    {
        return DB::transaction(function () use ($actor, $data) {
            $return = $this->returns->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'sales_return_number', 'SR'),
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? $this->inventory->defaultWarehouseFor($actor->tenant)?->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                SalesReturnItem::create([
                    'tenant_id' => $return->tenant_id,
                    'sales_return_id' => $return->id,
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
     * Receiving is the real warehouse event — stock moves IN. If the
     * return is linked to an invoice, this also generates and issues a
     * matching Credit Note automatically (a physical return should
     * always have a financial counterpart; leaving that as a separate
     * manual step is how returns silently never get credited).
     */
    public function receive(User $actor, SalesReturn $return): SalesReturn
    {
        if ($return->status !== 'draft') {
            throw new InvalidArgumentException("Sales return {$return->document_number} has already been received.");
        }

        $warehouse = $return->warehouse ?? $this->inventory->defaultWarehouseFor($actor->tenant);

        if (! $warehouse) {
            throw new InvalidArgumentException('No warehouse is configured for this return.');
        }

        return DB::transaction(function () use ($actor, $return, $warehouse) {
            foreach ($return->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_IN, (float) $item->quantity,
                    'sales_return', $return->id, "Returned via {$return->document_number}"
                );
            }

            $updates = ['status' => 'received', 'updated_by_user_id' => $actor->id];

            if ($return->sales_invoice_id) {
                $invoice = SalesInvoice::find($return->sales_invoice_id);
                $creditableItems = $return->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'vat_rate' => (float) $item->vat_rate,
                ])->all();

                $creditNote = $this->creditNoteService->create($actor, [
                    'sales_invoice_id' => $invoice->id,
                    'items' => $creditableItems,
                    'reason' => "Auto-generated from sales return {$return->document_number}: ".($return->reason ?? ''),
                ]);
                $creditNote = $this->creditNoteService->issue($actor, $creditNote);

                $updates['credit_note_id'] = $creditNote->id;
            }

            return $this->returns->update($return, $updates);
        });
    }
}
