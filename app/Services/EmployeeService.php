<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly SequenceService $sequences,
    ) {}

    /**
     * Creating an employee also provisions their leave balances for the
     * current calendar year against every active Leave Type — a real
     * integration, not a separate manual step HR would otherwise have
     * to remember for every hire.
     */
    public function create(User $actor, array $data): Employee
    {
        return DB::transaction(function () use ($actor, $data) {
            $employee = $this->employees->create(array_merge($data, [
                'tenant_id' => $actor->tenant_id,
                'employee_number' => $this->sequences->next($actor->tenant_id, 'employee_number', 'EMP'),
                'employment_status' => $data['employment_status'] ?? Employee::STATUS_ACTIVE,
                'created_by_user_id' => $actor->id,
            ]));

            $year = (int) now()->format('Y');
            foreach (LeaveType::where('tenant_id', $actor->tenant_id)->where('is_active', true)->get() as $leaveType) {
                LeaveBalance::create([
                    'tenant_id' => $actor->tenant_id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'allocated_days' => $leaveType->days_per_year,
                    'used_days' => 0,
                ]);
            }

            return $employee->fresh();
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return $this->employees->update($employee, $data);
    }

    /**
     * Real business-rule enforcement, not just a status flag: a
     * terminated employee can't be terminated again, and the
     * termination date is required so downstream payroll runs know
     * exactly which period they're still owed pay for.
     */
    public function terminate(Employee $employee, string $terminationDate): Employee
    {
        if ($employee->employment_status === Employee::STATUS_TERMINATED) {
            throw new InvalidArgumentException("Employee {$employee->employee_number} is already terminated.");
        }

        return $this->employees->update($employee, [
            'employment_status' => Employee::STATUS_TERMINATED,
            'termination_date' => $terminationDate,
        ]);
    }

    /**
     * The real "Salary Structure": replaces the employee's assigned
     * salary components wholesale with the given set — a full sync,
     * not an incremental patch, so removing a component is just
     * omitting it from the payload.
     *
     * @param array $components each: ['salary_component_id'=>, 'amount'=>]
     */
    public function assignSalaryComponents(Employee $employee, array $components): Employee
    {
        DB::transaction(function () use ($employee, $components) {
            $employee->salaryComponents()->delete();

            foreach ($components as $component) {
                $employee->salaryComponents()->create([
                    'tenant_id' => $employee->tenant_id,
                    'salary_component_id' => $component['salary_component_id'],
                    'amount' => $component['amount'],
                ]);
            }
        });

        return $employee->fresh('salaryComponents.salaryComponent');
    }
}
