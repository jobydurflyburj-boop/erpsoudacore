<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\HrPayrollProvisioningService;
use Illuminate\Console\Command;

/**
 * Backfills default Leave Types, Salary Components, and the two new
 * payroll accounting accounts (5200/2200) for tenants that registered
 * before the HR & Payroll module existed. New tenants get all of this
 * automatically via RegistrationService — this command exists purely
 * for that gap, mirroring ProvisionAccountingDefaultsCommand exactly.
 * Idempotent: HrPayrollProvisioningService::provisionDefaults() no-ops
 * for a tenant that already has leave types, so this is safe to re-run.
 */
class ProvisionHrDefaultsCommand extends Command
{
    protected $signature = 'hr:provision-defaults {tenant? : A specific tenant UUID, or omit to backfill every tenant}';

    protected $description = 'Provision default Leave Types, Salary Components, and payroll accounting accounts for tenants missing them';

    public function handle(HrPayrollProvisioningService $provisioning, TenantContext $context): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::where('id', $this->argument('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant(s) found.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $context->set($tenant);
            $context->apply();

            $provisioning->provisionDefaults($tenant);

            $this->info("Provisioned HR & Payroll defaults for tenant: {$tenant->name} ({$tenant->id})");
        }

        $context->reset();

        return self::SUCCESS;
    }
}
