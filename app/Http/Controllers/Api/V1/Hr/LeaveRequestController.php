<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaveRequests,
        private readonly LeaveService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->leaveRequests->paginate($request);
        $paginated->getCollection()->load(['employee', 'leaveType']);

        return LeaveRequestResource::collection($paginated);
    }

    public function store(StoreLeaveRequestRequest $request)
    {
        $employee = Employee::where('tenant_id', $request->user()->tenant_id)->findOrFail($request->validated()['employee_id']);

        try {
            $leaveRequest = $this->service->request($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['days_count' => $e->getMessage()]);
        }

        return $this->ok(new LeaveRequestResource($leaveRequest->load(['employee', 'leaveType'])), 201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        try {
            $leaveRequest = $this->service->approve($request->user(), $leaveRequest);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new LeaveRequestResource($leaveRequest->load(['employee', 'leaveType'])));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        try {
            $leaveRequest = $this->service->reject($request->user(), $leaveRequest);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new LeaveRequestResource($leaveRequest->load(['employee', 'leaveType'])));
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        try {
            $leaveRequest = $this->service->cancel($leaveRequest);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new LeaveRequestResource($leaveRequest));
    }
}
