<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\RecordSupplierPaymentRequest;
use App\Http\Requests\Purchase\StoreSupplierBillRequest;
use App\Http\Requests\Purchase\UpdateSupplierBillRequest;
use App\Http\Resources\SupplierBillResource;
use App\Http\Resources\SupplierPaymentResource;
use App\Models\GoodsReceipt;
use App\Models\SupplierBill;
use App\Repositories\Contracts\SupplierBillRepositoryInterface;
use App\Services\SupplierBillService;
use App\Services\SupplierPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SupplierBillController extends Controller
{
    public function __construct(
        private readonly SupplierBillRepositoryInterface $bills,
        private readonly SupplierBillService $service,
        private readonly SupplierPaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        return SupplierBillResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->bills->query()->with('supplier'))
                ->allowedFilters(['status', 'supplier_id'])->allowedSorts(['created_at', 'total', 'due_date'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreSupplierBillRequest $request)
    {
        $bill = $this->service->create($request->user(), $request->validated());
        return $this->ok(new SupplierBillResource($bill->load(['supplier', 'items.product'])), 201);
    }

    public function show(SupplierBill $supplierBill)
    {
        return $this->ok(new SupplierBillResource($supplierBill->load(['supplier', 'items.product', 'creator'])));
    }

    public function update(UpdateSupplierBillRequest $request, SupplierBill $supplierBill)
    {
        $bill = $this->service->update($request->user(), $supplierBill, $request->validated());
        return $this->ok(new SupplierBillResource($bill->load(['supplier', 'items.product'])));
    }

    public function fromGoodsReceipt(Request $request, GoodsReceipt $goodsReceipt)
    {
        try {
            $bill = $this->service->createFromGoodsReceipt($request->user(), $goodsReceipt);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SupplierBillResource($bill->load(['supplier', 'items.product'])), 201);
    }

    public function approve(Request $request, SupplierBill $supplierBill)
    {
        try {
            $bill = $this->service->approve($request->user(), $supplierBill);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SupplierBillResource($bill->load(['supplier', 'items.product'])));
    }

    public function destroy(SupplierBill $supplierBill)
    {
        $this->bills->delete($supplierBill);
        return response()->json(null, 204);
    }

    /** Convenience action: records a real SupplierPayment fully allocated to this one bill — the canonical path is POST /purchase/payments for multi-bill allocation. */
    public function recordPayment(RecordSupplierPaymentRequest $request, SupplierBill $supplierBill)
    {
        try {
            $payment = $this->paymentService->payBill($request->user(), $supplierBill, (float) $request->validated('amount'));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return $this->ok([
            'bill' => new SupplierBillResource($supplierBill->fresh(['supplier', 'items.product'])),
            'payment' => new SupplierPaymentResource($payment),
        ], 201);
    }
}
