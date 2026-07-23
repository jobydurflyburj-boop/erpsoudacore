<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Every widget below is either backed by a real query against this
 * codebase's own tables, or explicitly marked unavailable with the
 * future module that will provide it. Nothing here fabricates a number —
 * see docs/PLATFORM_ADMIN_MODULE.md "Dashboard — no fake data" for the
 * reasoning behind which widgets are real today vs. deferred.
 */
class DashboardService
{
    /** Widgets whose real source module doesn't exist in this codebase yet — CRM/Sales/Purchase/Inventory/Accounting/HR are explicitly out of scope for this pass. */
    private const DEFERRED_WIDGETS = [
        'revenue' => 'accounting',
        'expenses' => 'accounting',
        'customers' => 'crm',
        'leads' => 'crm',
        'sales_orders' => 'sales',
        'purchase_orders' => 'purchase',
        'inventory_summary' => 'inventory',
        'todays_attendance' => 'hr_payroll',
    ];

    private const DEFERRED_CHARTS = [
        'monthly_revenue' => 'accounting',
        'monthly_sales' => 'sales',
        'expenses' => 'accounting',
        'customer_growth' => 'crm',
    ];

    public function widgets(User $user): array
    {
        $widgets = [];

        foreach (self::DEFERRED_WIDGETS as $key => $module) {
            $widgets[$key] = $this->unavailable($module);
        }

        $widgets['employees'] = [
            'available' => true,
            'count' => User::where('status', User::STATUS_ACTIVE)->count(),
        ];

        $widgets['pending_approvals'] = [
            'available' => true,
            // "Approvals" as a cross-module concept (sales order approval,
            // expense approval, etc.) belongs to the modules that don't
            // exist yet. What IS real today: users who've been invited
            // but haven't completed setup — a genuine pending action for
            // whoever manages the account.
            'count' => User::where('status', User::STATUS_INVITED)->count(),
            'label' => 'Pending user invitations',
        ];

        $widgets['tasks'] = $this->taskSummary($user);
        $widgets['subscription_status'] = $this->subscriptionStatus($user->tenant);

        return $widgets;
    }

    public function charts(): array
    {
        $charts = [];

        foreach (self::DEFERRED_CHARTS as $key => $module) {
            $charts[$key] = $this->unavailable($module);
        }

        return $charts;
    }

    private function taskSummary(User $user): array
    {
        $base = Task::where('assigned_to_user_id', $user->id)
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED]);

        return [
            'available' => true,
            'pending' => (clone $base)->count(),
            'overdue' => (clone $base)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'due_today' => (clone $base)->whereDate('due_at', now()->toDateString())->count(),
        ];
    }

    private function subscriptionStatus(Tenant $tenant): array
    {
        return [
            'available' => true,
            'status' => $tenant->status,
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'days_remaining' => $tenant->trial_ends_at
                ? max(0, now()->diffInDays($tenant->trial_ends_at, false))
                : null,
        ];
    }

    private function unavailable(string $module): array
    {
        return [
            'available' => false,
            'module' => $module,
            'message' => "This widget will populate once the {$module} module is installed.",
        ];
    }

    public function recentActivities(User $user, int $limit = 15): Collection
    {
        $query = ActivityLog::query()->latest('created_at')->limit($limit);

        // A user without company-wide visibility (core.view) only sees
        // their own activity, not the whole tenant's — the dashboard
        // shouldn't be a bigger information-disclosure surface than the
        // dedicated Activity Log screen, which enforces the same gate.
        if (! $user->role?->hasPermission('core', 'view')) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    public function quickActions(User $user): array
    {
        $catalog = [
            ['key' => 'invite_user', 'label' => 'Invite User', 'permission' => 'admin.create', 'route' => '/admin/users'],
            ['key' => 'create_branch', 'label' => 'Create Branch', 'permission' => 'admin.create', 'route' => '/admin/branches'],
            ['key' => 'create_department', 'label' => 'Create Department', 'permission' => 'admin.create', 'route' => '/admin/departments'],
            ['key' => 'create_role', 'label' => 'Create Role', 'permission' => 'admin.create', 'route' => '/admin/roles'],
            ['key' => 'view_activity_log', 'label' => 'View Activity Log', 'permission' => 'core.view', 'route' => '/activity-logs'],
            ['key' => 'create_task', 'label' => 'Create Task', 'permission' => null, 'route' => '/tasks'],
        ];

        return array_values(array_filter($catalog, function ($action) use ($user) {
            if ($action['permission'] === null) {
                return true; // available to every authenticated user
            }

            [$module, $ability] = explode('.', $action['permission'], 2);

            return $user->role?->hasPermission($module, $ability) ?? false;
        }));
    }

    public function systemHealth(): array
    {
        $checkedAt = now()->toIso8601String();

        return [
            'checked_at' => $checkedAt,
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'down';
        }
    }

    private function checkCache(): string
    {
        try {
            $key = 'health-check:'.uniqid();
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);

            return $ok ? 'ok' : 'down';
        } catch (\Throwable) {
            return 'down';
        }
    }

    private function checkQueue(): string
    {
        if (config('queue.default') !== 'redis') {
            return 'not_configured';
        }

        try {
            Redis::connection()->ping();

            return 'ok';
        } catch (\Throwable) {
            return 'down';
        }
    }
}
