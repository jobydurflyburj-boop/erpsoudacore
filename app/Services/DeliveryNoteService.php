<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeliveryNoteService
{
    public function __construct(
        private readonly DeliveryNoteRepositoryInterface $deliveryNotes,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
    ) {}

    public function create(User $actor, array $data): DeliveryNote
    {
        return DB::transaction(function () use ($actor, $data) {
            $note = $this->deliveryNotes->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'delivery_note_number', 'DN'),
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? $this->inventory->defaultWarehouseFor($actor->tenant)?->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                DeliveryNoteItem::create([
                    'tenant_id' => $note->tenant_id,
                    'delivery_note_id' => $note->id,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $note;
        });
    }

    /** Delivering directly from a confirmed Sales Order — copies quantities across, same convert-pattern every other document chain in this project uses. */
    public function createFromSalesOrder(User $actor, SalesOrder $order): DeliveryNote
    {
        if ($order->status !== 'confirmed') {
            throw new InvalidArgumentException('Only a confirmed sales order can be delivered.');
        }

        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
        ])->all();

        return $this->create($actor, [
            'customer_id' => $order->customer_id,
            'sales_order_id' => $order->id,
            'items' => $items,
            'notes' => $order->notes,
        ]);
    }

    /** The real warehouse event — moves every line item's stock OUT. Rejects double-delivery. */
    public function deliver(User $actor, DeliveryNote $note): DeliveryNote
    {
        if ($note->status !== 'draft') {
            throw new InvalidArgumentException("Delivery note {$note->document_number} has already been delivered.");
        }

        $warehouse = $note->warehouse ?? $this->inventory->defaultWarehouseFor($actor->tenant);

        if (! $warehouse) {
            throw new InvalidArgumentException('No warehouse is configured for this delivery.');
        }

        return DB::transaction(function () use ($actor, $note, $warehouse) {
            foreach ($note->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_OUT, (float) $item->quantity,
                    'delivery_note', $note->id, "Delivered on {$note->document_number}"
                );
            }

            return $this->deliveryNotes->update($note, ['status' => 'delivered', 'updated_by_user_id' => $actor->id]);
        });
    }
}
