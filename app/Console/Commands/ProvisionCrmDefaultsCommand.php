<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\CrmProvisioningService;
use Illuminate\Console\Command;

/**
 * Backfills default Lead Sources/Statuses for tenants that registered
 * before the CRM module existed. New tenants get this automatically via
 * RegistrationService — this command exists purely for that gap.
 * Idempotent: CrmProvisioningService::provisionDefaults() no-ops for a
 * tenant that already has lead sources, so this is safe to re-run.
 */
class ProvisionCrmDefaultsCommand extends Command
{
    protected $signature = 'crm:provision-defaults {tenant? : A specific tenant UUID, or omit to backfill every tenant}';

    protected $description = 'Provision default Lead Sources and Lead Statuses for tenants missing them';

    public function handle(CrmProvisioningService $provisioning, TenantContext $context): int
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

            $this->info("Provisioned CRM defaults for tenant: {$tenant->name} ({$tenant->id})");
        }

        $context->reset();

        return self::SUCCESS;
    }
}
