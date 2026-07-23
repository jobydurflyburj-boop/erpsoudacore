<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferService
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transfers,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
    ) {}

    public function create(User $actor, array $data): StockTransfer
    {
        if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
            throw new InvalidArgumentException('Source and destination warehouses must be different.');
        }

        return DB::transaction(function () use ($actor, $data) {
            $transfer = $this->transfers->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'stock_transfer_number', 'ST'),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                StockTransferItem::create([
                    'tenant_id' => $transfer->tenant_id,
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer;
        });
    }

    /** The real warehouse event — moves every line item out of the source and into the destination, atomically. Rejects if already completed. */
    public function complete(User $actor, StockTransfer $transfer): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new InvalidArgumentException("Stock transfer {$transfer->document_number} has already been completed.");
        }

        $fromWarehouse = $transfer->fromWarehouse;
        $toWarehouse = $transfer->toWarehouse;

        return DB::transaction(function () use ($actor, $transfer, $fromWarehouse, $toWarehouse) {
            foreach ($transfer->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $fromWarehouse, StockMovement::TYPE_OUT, (float) $item->quantity,
                    'stock_transfer', $transfer->id, "Transferred to {$toWarehouse->name} via {$transfer->document_number}"
                );
                $this->inventory->adjustStock(
                    $actor, $item->product, $toWarehouse, StockMovement::TYPE_IN, (float) $item->quantity,
                    'stock_transfer', $transfer->id, "Transferred from {$fromWarehouse->name} via {$transfer->document_number}"
                );
            }

            return $this->transfers->update($transfer, ['status' => 'completed', 'updated_by_user_id' => $actor->id]);
        });
    }
}
