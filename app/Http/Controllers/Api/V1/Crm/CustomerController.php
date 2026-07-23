<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCustomerRequest;
use App\Http\Requests\Crm\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request)
    {
        $query = $this->customers->query()->with('accountManager');

        // Same record-level scoping as LeadController::index — Sales
        // sees only their own book of customers in the list too, not
        // just when loading one directly by ID (CustomerPolicy handles
        // that path).
        if (in_array($request->user()->role?->code, Customer::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('account_manager_user_id', $request->user()->id);
        }

        $paginated = \Spatie\QueryBuilder\QueryBuilder::for($query)
            ->allowedFilters(['status', 'customer_type', 'account_manager_user_id', 'country', 'city'])
            ->allowedSorts(['created_at', 'customer_number', 'credit_limit'])
            ->defaultSort('-created_at')
            ->paginate((int) $request->integer('page_size', 25));

        return CustomerResource::collection($paginated);
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->customerService->create($request->user(), $request->validated());

        return $this->ok(new CustomerResource($customer->load('accountManager')), 201);
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        return $this->ok(new CustomerResource($customer->load(['accountManager', 'creator', 'updater'])));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->update($request->user(), $customer, $request->validated());

        return $this->ok(new CustomerResource($customer->load('accountManager')));
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(null, 204);
    }
}
