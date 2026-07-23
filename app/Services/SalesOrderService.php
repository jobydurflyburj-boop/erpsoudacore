<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrders,
        private readonly SequenceService $sequences,
    ) {}

    public function create(User $actor, array $data): SalesOrder
    {
        return DB::transaction(function () use ($actor, $data) {
            $totals = $this->calculateTotals($data['items']);

            $order = $this->salesOrders->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'sales_order_number', 'SO'),
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceItems($order, $totals['lines']);

            return $order;
        });
    }

    /** Copies an accepted quotation's items straight across — real conversion, not a fresh manual re-entry. */
    public function createFromQuotation(User $actor, Quotation $quotation): SalesOrder
    {
        if ($quotation->status !== 'accepted') {
            throw new InvalidArgumentException('Only an accepted quotation can be converted to a sales order.');
        }

        $items = $quotation->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        return $this->create($actor, [
            'customer_id' => $quotation->customer_id,
            'quotation_id' => $quotation->id,
            'items' => $items,
            'notes' => $quotation->notes,
        ]);
    }

    public function update(User $actor, SalesOrder $order, array $data): SalesOrder
    {
        return DB::transaction(function () use ($actor, $order, $data) {
            $updates = ['updated_by_user_id' => $actor->id];

            if (array_key_exists('items', $data)) {
                $totals = $this->calculateTotals($data['items']);
                $updates = array_merge($updates, [
                    'subtotal' => $totals['subtotal'], 'vat_amount' => $totals['vat_amount'], 'total' => $totals['total'],
                ]);
                $order->items()->delete();
                $this->replaceItems($order, $totals['lines']);
            }

            foreach (['customer_id', 'document_date', 'notes', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            return $this->salesOrders->update($order, $updates)->fresh('items');
        });
    }

    private function replaceItems(SalesOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            SalesOrderItem::create([
                'tenant_id' => $order->tenant_id,
                'sales_order_id' => $order->id,
                'product_id' => $line['product_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'vat_rate' => $line['vat_rate'],
                'line_total' => $line['line_total'],
            ]);
        }
    }
}
