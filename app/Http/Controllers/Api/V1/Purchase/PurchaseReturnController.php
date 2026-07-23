<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\ReturnGoodsRequest;
use App\Http\Requests\Purchase\StorePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $returns,
        private readonly PurchaseReturnService $service,
    ) {}

    public function index(Request $request)
    {
        return PurchaseReturnResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->returns->query()->with('supplier'))
                ->allowedFilters(['status', 'supplier_id'])->allowedSorts(['created_at', 'document_date'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StorePurchaseReturnRequest $request)
    {
        $return = $this->service->create($request->user(), $request->validated());
        return $this->ok(new PurchaseReturnResource($return->load(['supplier', 'items.product'])), 201);
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return $this->ok(new PurchaseReturnResource($purchaseReturn->load(['supplier', 'items.product', 'creator', 'debitNote'])));
    }

    public function returnGoods(ReturnGoodsRequest $request, PurchaseReturn $purchaseReturn)
    {
        try {
            $return = $this->service->returnGoods($request->user(), $purchaseReturn, $request->validated('supplier_bill_id'));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new PurchaseReturnResource($return->load(['supplier', 'items.product', 'debitNote'])));
    }
}
