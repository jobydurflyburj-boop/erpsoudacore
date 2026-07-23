<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\ScheduledReportService;
use Illuminate\Console\Command;

/**
 * Runs every due Scheduled Report across every tenant — intended to
 * run on a real recurring schedule (e.g. hourly, via Laravel's
 * scheduler) in a real deployment. Mirrors the tenant-loop pattern
 * every other provisioning/backfill command in this project uses
 * (ProvisionHrDefaultsCommand, ProvisionAccountingDefaultsCommand),
 * since ScheduledReportService::process() operates within a resolved
 * tenant context.
 */
class ProcessScheduledReportsCommand extends Command
{
    protected $signature = 'reports:process-scheduled {tenant? : A specific tenant UUID, or omit to process every tenant}';

    protected $description = 'Send every due Scheduled Report for the given tenant (or all tenants)';

    public function handle(ScheduledReportService $service, TenantContext $context): int
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

            $result = $service->process();

            $this->info("Tenant {$tenant->name}: sent {$result['sent']}, skipped {$result['skipped']}");
        }

        $context->reset();

        return self::SUCCESS;
    }
}
