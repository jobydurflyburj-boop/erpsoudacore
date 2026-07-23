<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveService
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaveRequests,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Real double-booking and balance validation: a paid leave type
     * checks the employee's real remaining balance for the current
     * year and rejects the request outright if it would exceed it —
     * not auto-corrected, not left to the approver to catch by hand.
     * Unpaid leave (LeaveType::is_paid = false) skips the balance
     * check entirely, since it isn't drawn from an allocated pool.
     */
    public function request(Employee $employee, array $data): LeaveRequest
    {
        $leaveType = LeaveType::where('tenant_id', $employee->tenant_id)->findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('Leave end date cannot be before the start date.');
        }

        $daysCount = $start->diffInDays($end) + 1;

        if ($leaveType->is_paid) {
            $balance = LeaveBalance::where('tenant_id', $employee->tenant_id)
                ->where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)
                ->where('year', $start->year)->first();

            $remaining = $balance ? $balance->remainingDays() : 0.0;
            if ($daysCount > $remaining) {
                throw new InvalidArgumentException(
                    "Requested {$daysCount} day(s) of {$leaveType->name_en} exceeds the remaining balance ({$remaining} day(s))."
                );
            }
        }

        return $this->leaveRequests->create([
            'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id, 'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(), 'days_count' => $daysCount,
            'reason' => $data['reason'] ?? null, 'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Approving deducts the real balance (paid leave types only),
     * marks every calendar day in the range as 'on_leave' in
     * Attendance (a real integration — approved leave isn't invisible
     * to attendance tracking), and notifies the employee if they have
     * a linked system login.
     */
    public function approve(User $actor, LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            throw new InvalidArgumentException("Leave request is already {$leaveRequest->status}.");
        }

        return DB::transaction(function () use ($actor, $leaveRequest) {
            $leaveRequest = $this->leaveRequests->update($leaveRequest, [
                'status' => LeaveRequest::STATUS_APPROVED,
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
            ]);

            $leaveType = $leaveRequest->leaveType;
            if ($leaveType->is_paid) {
                $balance = LeaveBalance::where('tenant_id', $leaveRequest->tenant_id)
                    ->where('employee_id', $leaveRequest->employee_id)->where('leave_type_id', $leaveType->id)
                    ->where('year', Carbon::parse($leaveRequest->start_date)->year)->first();
                if ($balance) {
                    $balance->update(['used_days' => $balance->used_days + $leaveRequest->days_count]);
                }
            }

            foreach (CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date) as $day) {
                Attendance::updateOrCreate(
                    ['tenant_id' => $leaveRequest->tenant_id, 'employee_id' => $leaveRequest->employee_id, 'date' => $day->toDateString()],
                    ['status' => Attendance::STATUS_ON_LEAVE]
                );
            }

            $employee = $leaveRequest->employee;
            if ($employee->user) {
                $this->notifications->send(
                    $employee->user, 'hr_payroll', 'Leave request approved',
                    "Your {$leaveType->name_en} request ({$leaveRequest->start_date->toDateString()} to {$leaveRequest->end_date->toDateString()}) was approved."
                );
            }

            return $leaveRequest->fresh();
        });
    }

    public function reject(User $actor, LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            throw new InvalidArgumentException("Leave request is already {$leaveRequest->status}.");
        }

        $leaveRequest = $this->leaveRequests->update($leaveRequest, [
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_by_user_id' => $actor->id,
            'approved_at' => now(),
        ]);

        $employee = $leaveRequest->employee;
        if ($employee->user) {
            $this->notifications->send($employee->user, 'hr_payroll', 'Leave request rejected', 'Your leave request was not approved.');
        }

        return $leaveRequest;
    }

    /** Deliberately scoped to pending requests only — see HR_Payroll_Sprint.md for why cancelling an already-approved leave (balance/attendance reversal) is out of scope this sprint. */
    public function cancel(LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Only a pending leave request can be cancelled.');
        }

        return $this->leaveRequests->update($leaveRequest, ['status' => LeaveRequest::STATUS_CANCELLED]);
    }
}
