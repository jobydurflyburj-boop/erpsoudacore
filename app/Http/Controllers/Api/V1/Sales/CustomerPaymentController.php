<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\AllocatePaymentRequest;
use App\Http\Requests\Sales\StoreCustomerPaymentRequest;
use App\Http\Resources\CustomerPaymentResource;
use App\Http\Resources\PaymentAllocationResource;
use App\Models\CustomerPayment;
use App\Models\SalesInvoice;
use App\Repositories\Contracts\CustomerPaymentRepositoryInterface;
use App\Services\CustomerPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private readonly CustomerPaymentRepositoryInterface $payments,
        private readonly CustomerPaymentService $service,
    ) {}

    public function index(Request $request)
    {
        return CustomerPaymentResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->payments->query()->with('customer'))
                ->allowedFilters(['customer_id', 'payment_method'])->allowedSorts(['created_at', 'payment_date', 'amount'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreCustomerPaymentRequest $request)
    {
        try {
            $payment = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['allocations' => $e->getMessage()]);
        }
        return $this->ok(new CustomerPaymentResource($payment->load(['customer', 'allocations'])), 201);
    }

    public function show(CustomerPayment $payment)
    {
        return $this->ok(new CustomerPaymentResource($payment->load(['customer', 'allocations', 'creator'])));
    }

    public function allocate(AllocatePaymentRequest $request, CustomerPayment $payment)
    {
        try {
            $allocation = $this->service->allocate(
                $request->user(), $payment,
                SalesInvoice::findOrFail($request->validated('sales_invoice_id')),
                (float) $request->validated('amount')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }
        return $this->ok(new PaymentAllocationResource($allocation), 201);
    }
}
