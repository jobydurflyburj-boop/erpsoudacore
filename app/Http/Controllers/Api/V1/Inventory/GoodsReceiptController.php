<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreGoodsReceiptRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $receipts,
        private readonly GoodsReceiptService $service,
    ) {}

    public function index(Request $request)
    {
        return GoodsReceiptResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->receipts->query()->with(['warehouse', 'supplier']))
                ->allowedFilters(['status', 'warehouse_id', 'supplier_id', 'purchase_order_id'])->allowedSorts(['created_at'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreGoodsReceiptRequest $request)
    {
        $receipt = $this->service->create($request->user(), $request->validated());
        return $this->ok(new GoodsReceiptResource($receipt->load(['warehouse', 'supplier', 'items.product'])), 201);
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        return $this->ok(new GoodsReceiptResource($goodsReceipt->load(['warehouse', 'supplier', 'items.product', 'creator'])));
    }

    public function receive(Request $request, GoodsReceipt $goodsReceipt)
    {
        try {
            $receipt = $this->service->receive($request->user(), $goodsReceipt);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new GoodsReceiptResource($receipt->load(['warehouse', 'supplier', 'items.product'])));
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        $this->receipts->delete($goodsReceipt);
        return response()->json(null, 204);
    }
}
