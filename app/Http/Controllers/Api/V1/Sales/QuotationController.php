<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreQuotationRequest;
use App\Http\Requests\Sales\UpdateQuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\Quotation;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use App\Services\QuotationService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuotationController extends Controller
{
    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly QuotationService $service,
        private readonly SalesOrderService $salesOrderService,
    ) {}

    public function index(Request $request)
    {
        return QuotationResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->quotations->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id'])->allowedSorts(['created_at', 'total'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreQuotationRequest $request)
    {
        $quotation = $this->service->create($request->user(), $request->validated());
        return $this->ok(new QuotationResource($quotation->load(['customer', 'items.product'])), 201);
    }

    public function show(Quotation $quotation)
    {
        return $this->ok(new QuotationResource($quotation->load(['customer', 'items.product', 'creator'])));
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $quotation = $this->service->update($request->user(), $quotation, $request->validated());
        return $this->ok(new QuotationResource($quotation->load(['customer', 'items.product'])));
    }

    public function destroy(Quotation $quotation)
    {
        $this->quotations->delete($quotation);
        return response()->json(null, 204);
    }

    public function convertToSalesOrder(Request $request, Quotation $quotation)
    {
        try {
            $order = $this->salesOrderService->createFromQuotation($request->user(), $quotation);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SalesOrderResource($order->load(['customer', 'items.product'])), 201);
    }
}
