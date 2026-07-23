<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transfers,
        private readonly StockTransferService $service,
    ) {}

    public function index(Request $request)
    {
        return StockTransferResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->transfers->query()->with(['fromWarehouse', 'toWarehouse']))
                ->allowedFilters(['status', 'from_warehouse_id', 'to_warehouse_id'])->allowedSorts(['created_at'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreStockTransferRequest $request)
    {
        try {
            $transfer = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['to_warehouse_id' => $e->getMessage()]);
        }
        return $this->ok(new StockTransferResource($transfer->load(['fromWarehouse', 'toWarehouse', 'items.product'])), 201);
    }

    public function show(StockTransfer $stockTransfer)
    {
        return $this->ok(new StockTransferResource($stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator'])));
    }

    public function complete(Request $request, StockTransfer $stockTransfer)
    {
        try {
            $transfer = $this->service->complete($request->user(), $stockTransfer);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new StockTransferResource($transfer->load(['fromWarehouse', 'toWarehouse', 'items.product'])));
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        $this->transfers->delete($stockTransfer);
        return response()->json(null, 204);
    }
}
