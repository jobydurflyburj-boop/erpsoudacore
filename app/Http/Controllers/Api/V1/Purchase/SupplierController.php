<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StoreSupplierRequest;
use App\Http\Requests\Purchase\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\SequenceService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly SequenceService $sequences,
    ) {}

    public function index(Request $request)
    {
        return SupplierResource::collection($this->suppliers->paginate($request));
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->suppliers->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
            'supplier_number' => $this->sequences->next($request->user()->tenant_id, 'supplier_number', 'SUP'),
            'is_active' => true,
        ]));
        return $this->ok(new SupplierResource($supplier), 201);
    }

    public function show(Supplier $supplier)
    {
        return $this->ok(new SupplierResource($supplier));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        return $this->ok(new SupplierResource($this->suppliers->update($supplier, $request->validated())));
    }

    public function destroy(Supplier $supplier)
    {
        $this->suppliers->delete($supplier);
        return response()->json(null, 204);
    }
}
