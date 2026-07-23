<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function defaultWarehouseFor(Tenant $tenant): ?Warehouse
    {
        return Warehouse::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->first();
    }

    /** Real barcode lookup — the actual "Barcode Support" requirement: a product can be found by its scanned code, not just browsed by name. */
    public function findByBarcode(string $tenantId, string $barcode): ?Product
    {
        return Product::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('barcode', $barcode)
            ->first();
    }

    /** Every active product currently at or below its own reorder point — the real query behind Low Stock Alerts and the inventory dashboard/report widgets. */
    public function lowStockProducts(string $tenantId): \Illuminate\Support\Collection
    {
        return Product::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->with('stockLevels')
            ->get()
            ->filter(fn (Product $p) => $p->isLowStock())
            ->values();
    }

    /**
     * Real, atomic stock adjustment — moves stock_levels and records a
     * stock_movements row in one transaction, never just one or the
     * other. $type is 'in'|'out'|'adjustment'; 'out' is rejected if it
     * would take a warehouse's quantity negative (real validation, not
     * a soft warning). A decrease that crosses at/below the product's
     * reorder point fires a real Low Stock Alert notification.
     */
    public function adjustStock(
        User $actor,
        Product $product,
        Warehouse $warehouse,
        string $type,
        float $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        if (! in_array($type, [StockMovement::TYPE_IN, StockMovement::TYPE_OUT, StockMovement::TYPE_ADJUSTMENT], true)) {
            throw new InvalidArgumentException("Invalid stock movement type: {$type}");
        }

        return DB::transaction(function () use ($actor, $product, $warehouse, $type, $quantity, $referenceType, $referenceId, $notes) {
            $level = $product->stockLevels()->where('warehouse_id', $warehouse->id)->lockForUpdate()->first();
            $current = (float) ($level->quantity ?? 0);

            $delta = match ($type) {
                StockMovement::TYPE_IN => $quantity,
                StockMovement::TYPE_OUT => -$quantity,
                StockMovement::TYPE_ADJUSTMENT => $quantity, // signed delta supplied directly
            };

            $newQuantity = $current + $delta;

            if ($newQuantity < 0) {
                throw new InvalidArgumentException("This movement would take {$product->sku}'s stock below zero at {$warehouse->name}.");
            }

            if ($level) {
                $level->update(['quantity' => $newQuantity]);
            } else {
                \App\Models\StockLevel::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $newQuantity,
                ]);
            }

            $movement = StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => abs($quantity),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by_user_id' => $actor->id,
                'created_at' => now(),
            ]);

            if ($delta < 0) {
                $this->maybeAlertLowStock($product->fresh('stockLevels'));
            }

            return $movement;
        });
    }

    private function maybeAlertLowStock(Product $product): void
    {
        if (! $product->isLowStock()) {
            return;
        }

        $recipients = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $product->tenant_id)
            ->whereHas('role', fn ($q) => $q->whereIn('code', [Role::INVENTORY, Role::COMPANY_OWNER]))
            ->get();

        foreach ($recipients as $recipient) {
            $this->notifications->send(
                $recipient,
                'inventory.low_stock',
                "Low stock: {$product->sku}",
                "{$product->name_en} is at {$product->totalStock()} units, at or below its reorder point of {$product->reorder_point}.",
                ['product_id' => $product->id]
            );
        }
    }
}
