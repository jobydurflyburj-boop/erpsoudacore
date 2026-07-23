<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $adjustments,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
        private readonly InventoryAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($actor, $data) {
            $adjustment = $this->adjustments->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'stock_adjustment_number', 'ADJ'),
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                StockAdjustmentItem::create([
                    'tenant_id' => $adjustment->tenant_id,
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $item['product_id'],
                    'quantity_change' => $item['quantity_change'],
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * Approving is the real event: applies every line's signed
     * quantity_change to stock (via InventoryService, so negative-stock
     * protection still applies), then posts the real accounting value
     * impact valued at each product's current cost_price. A second-
     * approval attempt is rejected — an adjustment can't be applied twice.
     */
    public function approve(User $actor, StockAdjustment $adjustment): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new InvalidArgumentException("Stock adjustment {$adjustment->document_number} has already been approved.");
        }

        $warehouse = $adjustment->warehouse;

        return DB::transaction(function () use ($actor, $adjustment, $warehouse) {
            $totalValueChange = 0.0;

            foreach ($adjustment->items()->with('product')->get() as $item) {
                $change = (float) $item->quantity_change;

                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_ADJUSTMENT, $change,
                    'stock_adjustment', $adjustment->id, $item->reason ?? $adjustment->reason
                );

                $totalValueChange += $change * (float) $item->product->cost_price;
            }

            $adjustment = $this->adjustments->update($adjustment, [
                'status' => 'approved', 'approved_by_user_id' => $actor->id,
            ]);

            $this->accounting->postStockAdjustment($actor, $adjustment, round($totalValueChange, 2));

            return $adjustment;
        });
    }
}
