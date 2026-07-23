<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use InvalidArgumentException;

/**
 * Employee Self-Service: everything here is scoped server-side to the
 * calling user's own Employee record (via employees.user_id), never to
 * an id the client supplies — that's what makes this "self" service
 * rather than just the regular HR endpoints with a different URL
 * prefix. A user with no linked Employee record (most admin/system
 * accounts) gets a clear error, not an empty success response that
 * could be mistaken for "you have no data".
 */
class EmployeeSelfService
{
    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly AttendanceService $attendanceService,
    ) {}

    public function resolve(User $user): Employee
    {
        $employee = Employee::where('tenant_id', $user->tenant_id)->where('user_id', $user->id)->first();

        if (! $employee) {
            throw new InvalidArgumentException('Your account is not linked to an Employee record — ask HR to link it before using Employee Self-Service.');
        }

        return $employee;
    }

    public function requestOwnLeave(User $user, array $data): \App\Models\LeaveRequest
    {
        return $this->leaveService->request($this->resolve($user), $data);
    }

    public function checkInSelf(User $user): \App\Models\Attendance
    {
        return $this->attendanceService->checkIn($this->resolve($user));
    }

    public function checkOutSelf(User $user): \App\Models\Attendance
    {
        return $this->attendanceService->checkOut($this->resolve($user));
    }
}
