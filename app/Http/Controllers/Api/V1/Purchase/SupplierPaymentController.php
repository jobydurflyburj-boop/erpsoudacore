<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\AllocateSupplierPaymentRequest;
use App\Http\Requests\Purchase\StoreSupplierPaymentRequest;
use App\Http\Resources\SupplierPaymentAllocationResource;
use App\Http\Resources\SupplierPaymentResource;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use App\Services\SupplierPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SupplierPaymentController extends Controller
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $payments,
        private readonly SupplierPaymentService $service,
    ) {}

    public function index(Request $request)
    {
        return SupplierPaymentResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->payments->query()->with('supplier'))
                ->allowedFilters(['supplier_id', 'payment_method'])->allowedSorts(['created_at', 'payment_date', 'amount'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreSupplierPaymentRequest $request)
    {
        try {
            $payment = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['allocations' => $e->getMessage()]);
        }
        return $this->ok(new SupplierPaymentResource($payment->load(['supplier', 'allocations'])), 201);
    }

    public function show(SupplierPayment $payment)
    {
        return $this->ok(new SupplierPaymentResource($payment->load(['supplier', 'allocations', 'creator'])));
    }

    public function allocate(AllocateSupplierPaymentRequest $request, SupplierPayment $payment)
    {
        try {
            $allocation = $this->service->allocate(
                $request->user(), $payment,
                SupplierBill::findOrFail($request->validated('supplier_bill_id')),
                (float) $request->validated('amount')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }
        return $this->ok(new SupplierPaymentAllocationResource($allocation), 201);
    }
}
