<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every figure here is a real cross-tenant count — reachable only
 * because a Super Admin session has is_super_admin=true, which is what
 * lets these queries see rows belonging to every tenant (see
 * BelongsToTenant's global scope and the RLS policies it mirrors).
 *
 * No revenue/MRR figure is reported — there is no billing engine in
 * this codebase (see docs/AUDIT_REPORT.md Part 6), and fabricating one
 * would violate this project's standing "no fake data" rule the same
 * way the tenant-facing dashboards already honor it. Only genuinely
 * countable things are reported.
 */
class PlatformMetricsService
{
    public function summary(): array
    {
        return [
            'tenants' => $this->tenantCounts(),
            'users_total' => User::withoutGlobalScope('tenant')->count(),
            'leads_total' => Lead::withoutGlobalScope('tenant')->count(),
            'new_tenants_this_month' => Tenant::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            'signups_last_6_months' => $this->signupsLast6Months(),
        ];
    }

    private function tenantCounts(): array
    {
        $counts = Tenant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $statuses = ['trial', 'active', 'past_due', 'suspended', 'cancelled'];

        return [
            'total' => (int) $counts->sum(),
            'by_status' => collect($statuses)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)]),
        ];
    }

    private function signupsLast6Months(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        return $months->map(function ($month) {
            return [
                'month' => $month->format('Y-m'),
                'count' => Tenant::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        })->all();
    }
}
