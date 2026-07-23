<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCustomerActivityRequest;
use App\Http\Resources\CustomerActivityResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerActivityRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerActivityController extends Controller
{
    public function __construct(
        private readonly CustomerActivityRepositoryInterface $activities,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        return CustomerActivityResource::collection($this->activities->timelineFor($customer, $request));
    }

    public function store(StoreCustomerActivityRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $activity = $this->customerService->logManualActivity(
            $request->user(), $customer, $request->validated('type'), $request->validated('description')
        );

        return $this->ok(new CustomerActivityResource($activity->load('user')), 201);
    }
}
