<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Tenant;

/**
 * Seeds a generic, real chart of accounts at registration — same role
 * RoleProvisioningService/CrmProvisioningService play for their domains.
 * Fully editable afterward. A KSA-specific COA template wired to ZATCA
 * reporting is still V2 — see docs/MVP_DEMO.md.
 *
 * As of the Accounting Module completion sprint, input and output VAT
 * are split into separate accounts (2100 VAT Payable for output tax
 * collected on sales, 2110 VAT Recoverable for input tax paid on
 * purchases) — the Sales and Purchase sprints had deliberately netted
 * these through one account as a named simplification; this sprint
 * closes that gap the same way CRM Sprint 3 closed its own backfill gap
 * for Opportunity stages: a guarded existence check plus a console
 * command for tenants that registered before this account existed.
 */
class AccountingProvisioningService
{
    private function defaultAccounts(): array
    {
        return [
            ['1000', 'Cash', 'النقدية', 'asset'],
            ['1100', 'Accounts Receivable', 'الذمم المدينة', 'asset'],
            ['1200', 'Inventory', 'المخزون', 'asset'],
            ['2000', 'Accounts Payable', 'الذمم الدائنة', 'liability'],
            ['2100', 'VAT Payable', 'ضريبة القيمة المضافة المستحقة', 'liability'],
            ['3000', 'Owner\'s Equity', 'حقوق الملكية', 'equity'],
            ['4000', 'Sales Revenue', 'إيرادات المبيعات', 'revenue'],
            ['5000', 'Cost of Goods Sold', 'تكلفة البضاعة المباعة', 'expense'],
            ['5100', 'Operating Expenses', 'مصاريف التشغيل', 'expense'],
        ];
    }

    private function vatRecoverableAccount(): array
    {
        return ['2110', 'VAT Recoverable', 'ضريبة القيمة المضافة القابلة للاسترداد', 'asset'];
    }

    public function provisionDefaults(Tenant $tenant): void
    {
        if (ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            $this->provisionVatRecoverableIfMissing($tenant); // backfill path — see class docblock

            return; // core accounts already provisioned — safe to call repeatedly
        }

        foreach ($this->defaultAccounts() as [$code, $nameEn, $nameAr, $type]) {
            ChartOfAccount::create([
                'tenant_id' => $tenant->id,
                'code' => $code,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'type' => $type,
                'is_active' => true,
            ]);
        }

        $this->provisionVatRecoverableIfMissing($tenant);
    }

    /**
     * Split from the core-accounts existence check above: a tenant that
     * registered before this sprint added the 2110 account already has
     * chart_of_accounts rows, which would otherwise short-circuit this
     * whole method via the guard at the top — this second check lets
     * `accounting:provision-defaults` backfill JUST the new account for
     * such a tenant, without re-provisioning (and duplicating) the nine
     * it already has.
     */
    private function provisionVatRecoverableIfMissing(Tenant $tenant): void
    {
        [$code, $nameEn, $nameAr, $type] = $this->vatRecoverableAccount();

        if (ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
            return;
        }

        ChartOfAccount::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'type' => $type,
            'is_active' => true,
        ]);
    }
}
