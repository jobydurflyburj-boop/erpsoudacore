<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Resources\StockLevelResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function levels(Request $request)
    {
        $levels = \App\Models\StockLevel::with(['product', 'warehouse'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->string('product_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->string('warehouse_id')))
            ->paginate((int) $request->integer('page_size', 50));

        return StockLevelResource::collection($levels);
    }

    public function movements(Request $request)
    {
        $movements = StockMovement::with(['product', 'warehouse', 'creator'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->string('product_id')))
            ->latest('created_at')
            ->paginate((int) $request->integer('page_size', 50));

        return StockMovementResource::collection($movements);
    }

    /** Real Low Stock Alerts, surfaced as a queryable list — the same query InventoryService::adjustStock uses to decide whether to fire a notification. */
    public function lowStock(Request $request)
    {
        $products = $this->inventory->lowStockProducts($request->user()->tenant_id);

        return $this->ok(\App\Http\Resources\ProductResource::collection($products));
    }

    public function adjust(AdjustStockRequest $request)
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $warehouse = Warehouse::findOrFail($request->validated('warehouse_id'));

        try {
            $movement = $this->inventory->adjustStock(
                $request->user(), $product, $warehouse,
                $request->validated('type'), (float) $request->validated('quantity'),
                'manual', null, $request->validated('notes')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return $this->ok(new StockMovementResource($movement->load(['product', 'warehouse', 'creator'])), 201);
    }
}
