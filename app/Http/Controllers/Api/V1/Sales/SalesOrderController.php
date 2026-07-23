<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesOrderRequest;
use App\Http\Requests\Sales\UpdateSalesOrderRequest;
use App\Http\Resources\SalesInvoiceResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Services\SalesInvoiceService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrders,
        private readonly SalesOrderService $service,
        private readonly SalesInvoiceService $invoiceService,
    ) {}

    public function index(Request $request)
    {
        return SalesOrderResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->salesOrders->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id'])->allowedSorts(['created_at', 'total'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreSalesOrderRequest $request)
    {
        $order = $this->service->create($request->user(), $request->validated());
        return $this->ok(new SalesOrderResource($order->load(['customer', 'items.product'])), 201);
    }

    public function show(SalesOrder $salesOrder)
    {
        return $this->ok(new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator'])));
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder)
    {
        $order = $this->service->update($request->user(), $salesOrder, $request->validated());
        return $this->ok(new SalesOrderResource($order->load(['customer', 'items.product'])));
    }

    public function destroy(SalesOrder $salesOrder)
    {
        $this->salesOrders->delete($salesOrder);
        return response()->json(null, 204);
    }

    public function convertToInvoice(Request $request, SalesOrder $salesOrder)
    {
        try {
            $invoice = $this->invoiceService->createFromSalesOrder($request->user(), $salesOrder);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SalesInvoiceResource($invoice->load(['customer', 'items.product'])), 201);
    }
}
