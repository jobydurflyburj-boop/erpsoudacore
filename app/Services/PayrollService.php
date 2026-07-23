<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Repositories\Contracts\PayrollRunRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The real gross-to-net payroll engine: basic salary + assigned
 * allowances + approved overtime for the period − assigned deductions.
 * Deliberately generic rather than Saudi-specific — no hardcoded GOSI
 * rate or income-tax bracket, since those are real regulatory input
 * this project has never been given (see PROJECT_STATUS.md's standing
 * note). A tenant models GOSI today as a real percentage-of-basic
 * deduction Salary Component, computed and stored the same way any
 * other deduction is — not guessed at here.
 */
class PayrollService
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $payrollRuns,
        private readonly HrPayrollAccountingIntegrationService $accountingIntegration,
    ) {}

    /**
     * Processes one payroll run for every active employee in a single
     * transaction: computes and stores a real Payslip (with line-item
     * detail) per employee, rolls the totals up onto the run, and
     * posts one real balanced journal entry for the whole run — not
     * per-employee, since the accounting event is the payroll run as a
     * whole, mirroring how a Sales Invoice posts once regardless of
     * how many line items it has.
     */
    public function process(User $actor, int $month, int $year): PayrollRun
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Payroll period month must be between 1 and 12.');
        }

        if (PayrollRun::where('tenant_id', $actor->tenant_id)->where('period_month', $month)->where('period_year', $year)->exists()) {
            throw new InvalidArgumentException("A payroll run for {$month}/{$year} already exists.");
        }

        $employees = Employee::where('tenant_id', $actor->tenant_id)
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->with('salaryComponents.salaryComponent')
            ->get();

        if ($employees->isEmpty()) {
            throw new InvalidArgumentException('No active employees to process payroll for.');
        }

        return DB::transaction(function () use ($actor, $month, $year, $employees) {
            $run = $this->payrollRuns->create([
                'tenant_id' => $actor->tenant_id,
                'run_number' => 'PR-'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'period_month' => $month, 'period_year' => $year, 'status' => PayrollRun::STATUS_DRAFT,
                'created_by_user_id' => $actor->id,
            ]);

            $totalGross = 0.0;
            $totalDeductions = 0.0;
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                $payslip = $this->generatePayslip($run, $employee, $month, $year);
                $totalGross += (float) $payslip->gross_pay;
                $totalDeductions += (float) $payslip->total_deductions;
                $totalNet += (float) $payslip->net_pay;
            }

            $run = $this->payrollRuns->update($run, [
                'status' => PayrollRun::STATUS_PROCESSED,
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
                'processed_at' => now(),
            ]);

            $this->accountingIntegration->postPayrollRunProcessed($actor, $run);

            return $run->fresh('payslips');
        });
    }

    private function generatePayslip(PayrollRun $run, Employee $employee, int $month, int $year): Payslip
    {
        $basic = (float) $employee->basic_salary;
        $allowances = 0.0;
        $deductions = 0.0;
        $lines = [['label' => 'Basic Salary', 'type' => 'basic', 'amount' => $basic, 'salary_component_id' => null]];

        foreach ($employee->salaryComponents as $assigned) {
            $component = $assigned->salaryComponent;
            $amount = (float) $assigned->amount;

            if ($component->type === SalaryComponent::TYPE_ALLOWANCE) {
                $allowances += $amount;
            } else {
                $deductions += $amount;
            }

            $lines[] = ['label' => $component->name_en, 'type' => $component->type, 'amount' => $amount, 'salary_component_id' => $component->id];
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $overtimeAmount = (float) OvertimeRecord::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)->where('status', OvertimeRecord::STATUS_APPROVED)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount');

        if ($overtimeAmount > 0) {
            $lines[] = ['label' => 'Overtime', 'type' => 'overtime', 'amount' => $overtimeAmount, 'salary_component_id' => null];
        }

        $grossPay = round($basic + $allowances + $overtimeAmount, 2);
        $netPay = round($grossPay - $deductions, 2);

        $payslip = Payslip::create([
            'tenant_id' => $employee->tenant_id, 'payroll_run_id' => $run->id, 'employee_id' => $employee->id,
            'basic_salary' => $basic, 'total_allowances' => round($allowances, 2), 'overtime_amount' => round($overtimeAmount, 2),
            'total_deductions' => round($deductions, 2), 'gross_pay' => $grossPay, 'net_pay' => $netPay,
            'status' => Payslip::STATUS_GENERATED,
        ]);

        foreach ($lines as $line) {
            PayslipLine::create(array_merge($line, ['tenant_id' => $employee->tenant_id, 'payslip_id' => $payslip->id]));
        }

        return $payslip;
    }

    public function markPaid(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_PROCESSED) {
            throw new InvalidArgumentException('Only a processed payroll run can be marked paid.');
        }

        DB::transaction(function () use ($run) {
            $run->payslips()->update(['status' => Payslip::STATUS_PAID, 'paid_at' => now()]);
            $this->payrollRuns->update($run, ['status' => PayrollRun::STATUS_PAID]);
        });

        return $run->fresh('payslips');
    }
}
