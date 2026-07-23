<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $adjustments,
        private readonly StockAdjustmentService $service,
    ) {}

    public function index(Request $request)
    {
        return StockAdjustmentResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->adjustments->query()->with('warehouse'))
                ->allowedFilters(['status', 'warehouse_id'])->allowedSorts(['created_at'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        $adjustment = $this->service->create($request->user(), $request->validated());
        return $this->ok(new StockAdjustmentResource($adjustment->load(['warehouse', 'items.product'])), 201);
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $this->ok(new StockAdjustmentResource($stockAdjustment->load(['warehouse', 'items.product', 'creator', 'approver'])));
    }

    public function approve(Request $request, StockAdjustment $stockAdjustment)
    {
        try {
            $adjustment = $this->service->approve($request->user(), $stockAdjustment);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new StockAdjustmentResource($adjustment->load(['warehouse', 'items.product', 'approver'])));
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        $this->adjustments->delete($stockAdjustment);
        return response()->json(null, 204);
    }
}
