<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;
use InvalidArgumentException;

class AttendanceService
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendances) {}

    /**
     * Real shift-aware lateness: if the employee has an assigned shift,
     * checking in more than 10 minutes after the shift's start_time
     * marks the day 'late' rather than 'present' — not just a
     * timestamp with no judgment applied to it.
     */
    public function checkIn(Employee $employee): Attendance
    {
        $today = now()->toDateString();
        $existing = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)->where('date', $today)->first();

        if ($existing && $existing->check_in) {
            throw new InvalidArgumentException("Employee {$employee->employee_number} has already checked in today.");
        }

        $now = now();
        $status = Attendance::STATUS_PRESENT;

        if ($employee->shift) {
            $shiftStart = Carbon::parse($today.' '.$employee->shift->start_time);
            if ($now->greaterThan($shiftStart->addMinutes(10))) {
                $status = Attendance::STATUS_LATE;
            }
        }

        return $this->attendances->create([
            'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => $today,
            'check_in' => $now, 'status' => $status, 'shift_id' => $employee->shift_id,
        ]);
    }

    public function checkOut(Employee $employee): Attendance
    {
        $today = now()->toDateString();
        $attendance = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)->where('date', $today)->first();

        if (! $attendance || ! $attendance->check_in) {
            throw new InvalidArgumentException("Employee {$employee->employee_number} has not checked in today.");
        }
        if ($attendance->check_out) {
            throw new InvalidArgumentException("Employee {$employee->employee_number} has already checked out today.");
        }

        $now = now();
        $hours = round($attendance->check_in->diffInMinutes($now) / 60, 2);

        return $this->attendances->update($attendance, ['check_out' => $now, 'hours_worked' => $hours]);
    }

    /** HR marking attendance manually for a past/explicit date — absence, on-leave, half-day. */
    public function mark(Employee $employee, string $date, string $status): Attendance
    {
        return Attendance::updateOrCreate(
            ['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => $date],
            ['status' => $status, 'shift_id' => $employee->shift_id]
        );
    }
}
