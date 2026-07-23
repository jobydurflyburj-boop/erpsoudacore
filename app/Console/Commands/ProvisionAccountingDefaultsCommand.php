<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\AccountingProvisioningService;
use Illuminate\Console\Command;

/**
 * Backfills the VAT Recoverable account (2110) for tenants that
 * registered before the Accounting Module completion sprint added it.
 * New tenants get the full chart of accounts, including this one,
 * automatically via RegistrationService — this command exists purely
 * for that gap, mirroring ProvisionCrmDefaultsCommand exactly.
 * Idempotent: AccountingProvisioningService::provisionDefaults()
 * no-ops for a tenant that already has the account, so this is safe to
 * re-run.
 */
class ProvisionAccountingDefaultsCommand extends Command
{
    protected $signature = 'accounting:provision-defaults {tenant? : A specific tenant UUID, or omit to backfill every tenant}';

    protected $description = 'Provision the default chart of accounts (and backfill any missing standard accounts) for tenants missing them';

    public function handle(AccountingProvisioningService $provisioning, TenantContext $context): int
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

            $this->info("Provisioned accounting defaults for tenant: {$tenant->name} ({$tenant->id})");
        }

        $context->reset();

        return self::SUCCESS;
    }
}
