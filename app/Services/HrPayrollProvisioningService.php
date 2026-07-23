<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\LeaveType;
use App\Models\SalaryComponent;
use App\Models\Tenant;

/**
 * Seeds sensible HR & Payroll defaults for a tenant — same role
 * CrmProvisioningService/AccountingProvisioningService play for their
 * domains. Fully editable afterward via the LeaveType/SalaryComponent
 * management endpoints.
 *
 * Also backfills two new chart-of-accounts entries this sprint adds —
 * `5200 Salaries & Wages Expense` and `2200 Salaries Payable` — the
 * same guarded-existence-check + console-command backfill pattern the
 * Accounting Module completion sprint used for `2110 VAT Recoverable`.
 * Deliberately generic: no GOSI-specific deduction is pre-seeded, since
 * GOSI's actual computation rules are real regulatory input this
 * project has never been given (see PROJECT_STATUS.md's standing
 * note) — a tenant can model GOSI as a percentage-of-basic deduction
 * component today via the real Salary Components engine once they
 * have those rules, rather than this sprint guessing at them.
 */
class HrPayrollProvisioningService
{
    private function defaultLeaveTypes(): array
    {
        // name_en => [name_ar, days_per_year, is_paid]
        return [
            'Annual Leave' => ['إجازة سنوية', 21, true],
            'Sick Leave' => ['إجازة مرضية', 15, true],
            'Unpaid Leave' => ['إجازة بدون راتب', 0, false],
        ];
    }

    private function defaultSalaryComponents(): array
    {
        // name_en => [name_ar, type, calculation_type, default_amount, is_taxable]
        return [
            'Housing Allowance' => ['بدل سكن', SalaryComponent::TYPE_ALLOWANCE, SalaryComponent::CALC_PERCENTAGE_OF_BASIC, 25.00, false],
            'Transport Allowance' => ['بدل نقل', SalaryComponent::TYPE_ALLOWANCE, SalaryComponent::CALC_FIXED, 500.00, false],
        ];
    }

    private function payrollAccounts(): array
    {
        return [
            ['5200', 'Salaries & Wages Expense', 'مصاريف الرواتب والأجور', 'expense'],
            ['2200', 'Salaries Payable', 'رواتب مستحقة الدفع', 'liability'],
        ];
    }

    public function provisionDefaults(Tenant $tenant): void
    {
        if (LeaveType::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            $this->provisionPayrollAccountsIfMissing($tenant); // backfill path — see class docblock

            return; // leave types already provisioned — safe to call repeatedly
        }

        foreach ($this->defaultLeaveTypes() as $nameEn => [$nameAr, $days, $isPaid]) {
            LeaveType::create([
                'tenant_id' => $tenant->id, 'name_en' => $nameEn, 'name_ar' => $nameAr,
                'days_per_year' => $days, 'is_paid' => $isPaid, 'is_active' => true,
            ]);
        }

        foreach ($this->defaultSalaryComponents() as $nameEn => [$nameAr, $type, $calcType, $amount, $taxable]) {
            SalaryComponent::create([
                'tenant_id' => $tenant->id, 'name_en' => $nameEn, 'name_ar' => $nameAr,
                'type' => $type, 'calculation_type' => $calcType, 'default_amount' => $amount,
                'is_taxable' => $taxable, 'is_active' => true,
            ]);
        }

        $this->provisionPayrollAccountsIfMissing($tenant);
    }

    /**
     * Split from the leave-types existence check above, mirroring
     * AccountingProvisioningService::provisionVatRecoverableIfMissing()
     * exactly: lets `hr:provision-defaults` backfill JUST the new
     * accounts for a tenant that already has leave types/components,
     * without re-provisioning (and duplicating) those.
     */
    private function provisionPayrollAccountsIfMissing(Tenant $tenant): void
    {
        foreach ($this->payrollAccounts() as [$code, $nameEn, $nameAr, $type]) {
            if (ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
                continue;
            }

            ChartOfAccount::create([
                'tenant_id' => $tenant->id, 'code' => $code, 'name_en' => $nameEn,
                'name_ar' => $nameAr, 'type' => $type, 'is_active' => true,
            ]);
        }
    }
}
