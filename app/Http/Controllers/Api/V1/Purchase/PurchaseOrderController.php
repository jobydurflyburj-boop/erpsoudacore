<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseOrderRequest;
use App\Http\Requests\Purchase\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderService $service,
    ) {}

    public function index(Request $request)
    {
        return PurchaseOrderResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->purchaseOrders->query()->with('supplier'))
                ->allowedFilters(['status', 'supplier_id'])->allowedSorts(['created_at', 'total'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $po = $this->service->create($request->user(), $request->validated());
        return $this->ok(new PurchaseOrderResource($po->load(['supplier', 'items.product'])), 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $this->ok(new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product', 'creator'])));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $po = $this->service->update($request->user(), $purchaseOrder, $request->validated());
        return $this->ok(new PurchaseOrderResource($po->load(['supplier', 'items.product'])));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $po = $this->service->receive($request->user(), $purchaseOrder);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new PurchaseOrderResource($po->load(['supplier', 'items.product'])));
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrders->delete($purchaseOrder);
        return response()->json(null, 204);
    }
}
