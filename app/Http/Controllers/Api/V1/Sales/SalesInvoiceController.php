<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\RecordPaymentRequest;
use App\Http\Requests\Sales\StoreSalesInvoiceRequest;
use App\Http\Requests\Sales\UpdateSalesInvoiceRequest;
use App\Http\Resources\CustomerPaymentResource;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\SalesInvoice;
use App\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Services\CustomerPaymentService;
use App\Services\SalesInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoices,
        private readonly SalesInvoiceService $service,
        private readonly CustomerPaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        return SalesInvoiceResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->invoices->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id'])->allowedSorts(['created_at', 'total', 'due_date'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreSalesInvoiceRequest $request)
    {
        $invoice = $this->service->create($request->user(), $request->validated());
        return $this->ok(new SalesInvoiceResource($invoice->load(['customer', 'items.product'])), 201);
    }

    public function show(SalesInvoice $salesInvoice)
    {
        return $this->ok(new SalesInvoiceResource($salesInvoice->load(['customer', 'items.product', 'creator', 'creditNotes'])));
    }

    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice)
    {
        $invoice = $this->service->update($request->user(), $salesInvoice, $request->validated());
        return $this->ok(new SalesInvoiceResource($invoice->load(['customer', 'items.product'])));
    }

    public function issue(Request $request, SalesInvoice $salesInvoice)
    {
        try {
            $invoice = $this->service->issue($request->user(), $salesInvoice);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SalesInvoiceResource($invoice->load(['customer', 'items.product'])));
    }

    /** Convenience action: records a real CustomerPayment fully allocated to this one invoice — the canonical path is POST /sales/payments for multi-invoice allocation. */
    public function recordPayment(RecordPaymentRequest $request, SalesInvoice $salesInvoice)
    {
        try {
            $payment = $this->paymentService->payInvoice($request->user(), $salesInvoice, (float) $request->validated('amount'));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }
        $invoice = $salesInvoice->fresh(['customer', 'items.product']);

        return $this->ok([
            'invoice' => new SalesInvoiceResource($invoice),
            'payment' => new CustomerPaymentResource($payment),
        ], 201);
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        $this->invoices->delete($salesInvoice);
        return response()->json(null, 204);
    }
}
