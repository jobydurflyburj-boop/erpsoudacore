<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\User;
use App\Repositories\Contracts\OvertimeRecordRepositoryInterface;
use InvalidArgumentException;

class OvertimeService
{
    // A documented assumption, not a guess left implicit: hourly rate =
    // basic_salary / STANDARD_MONTHLY_HOURS (8-hour day x 30-day
    // month). This is a defensible generic default — the real,
    // contract-specific standard-hours figure is exactly the kind of
    // business input the project's standing GOSI note names as
    // currently missing; a tenant with a different standard workweek
    // would need this made configurable, which is out of scope this
    // sprint (see HR_Payroll_Sprint.md).
    private const STANDARD_MONTHLY_HOURS = 240;

    public function __construct(private readonly OvertimeRecordRepositoryInterface $overtimeRecords) {}

    public function request(Employee $employee, array $data): OvertimeRecord
    {
        $hourlyRate = (float) $employee->basic_salary / self::STANDARD_MONTHLY_HOURS;
        $rateMultiplier = (float) ($data['rate_multiplier'] ?? 1.50);
        $amount = round($hourlyRate * (float) $data['hours'] * $rateMultiplier, 2);

        return $this->overtimeRecords->create([
            'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id,
            'date' => $data['date'], 'hours' => $data['hours'], 'rate_multiplier' => $rateMultiplier,
            'amount' => $amount, 'status' => OvertimeRecord::STATUS_PENDING,
        ]);
    }

    public function approve(User $actor, OvertimeRecord $overtime): OvertimeRecord
    {
        if ($overtime->status !== OvertimeRecord::STATUS_PENDING) {
            throw new InvalidArgumentException("Overtime record is already {$overtime->status}.");
        }

        return $this->overtimeRecords->update($overtime, ['status' => OvertimeRecord::STATUS_APPROVED, 'approved_by_user_id' => $actor->id]);
    }

    public function reject(User $actor, OvertimeRecord $overtime): OvertimeRecord
    {
        if ($overtime->status !== OvertimeRecord::STATUS_PENDING) {
            throw new InvalidArgumentException("Overtime record is already {$overtime->status}.");
        }

        return $this->overtimeRecords->update($overtime, ['status' => OvertimeRecord::STATUS_REJECTED, 'approved_by_user_id' => $actor->id]);
    }
}
