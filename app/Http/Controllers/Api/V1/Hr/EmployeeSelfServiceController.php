<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\SelfLeaveRequestRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\PayslipResource;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Services\EmployeeSelfService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Every action here resolves the employee from the authenticated
 * user, never from a client-supplied id — see EmployeeSelfService's
 * docblock. This is what makes Employee Self-Service safe to grant
 * broadly (ess.view/ess.create) to every default role without also
 * granting access to hr_payroll's full employee data.
 */
class EmployeeSelfServiceController extends Controller
{
    public function __construct(private readonly EmployeeSelfService $service) {}

    public function profile(Request $request)
    {
        try {
            $employee = $this->service->resolve($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['employee' => $e->getMessage()]);
        }

        return $this->ok(new EmployeeResource($employee->load(['department', 'designation', 'shift'])));
    }

    public function attendance(Request $request)
    {
        try {
            $employee = $this->service->resolve($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['employee' => $e->getMessage()]);
        }

        $records = Attendance::where('tenant_id', $employee->tenant_id)->where('employee_id', $employee->id)
            ->orderByDesc('date')->limit(60)->get();

        return AttendanceResource::collection($records);
    }

    public function checkIn(Request $request)
    {
        try {
            $attendance = $this->service->checkInSelf($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new AttendanceResource($attendance), 201);
    }

    public function checkOut(Request $request)
    {
        try {
            $attendance = $this->service->checkOutSelf($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new AttendanceResource($attendance));
    }

    public function leaveRequests(Request $request)
    {
        try {
            $employee = $this->service->resolve($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['employee' => $e->getMessage()]);
        }

        $requests = LeaveRequest::where('tenant_id', $employee->tenant_id)->where('employee_id', $employee->id)
            ->with('leaveType')->orderByDesc('created_at')->get();

        return LeaveRequestResource::collection($requests);
    }

    public function requestLeave(SelfLeaveRequestRequest $request)
    {
        try {
            $leaveRequest = $this->service->requestOwnLeave($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['days_count' => $e->getMessage()]);
        }

        return $this->ok(new LeaveRequestResource($leaveRequest->load('leaveType')), 201);
    }

    public function payslips(Request $request)
    {
        try {
            $employee = $this->service->resolve($request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['employee' => $e->getMessage()]);
        }

        $payslips = Payslip::where('tenant_id', $employee->tenant_id)->where('employee_id', $employee->id)
            ->with('lines')->orderByDesc('created_at')->get();

        return PayslipResource::collection($payslips);
    }
}
