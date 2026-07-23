<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;

/**
 * A dedicated HR & Payroll dashboard — mirrors SalesDashboardService/
 * PurchaseDashboardService exactly, same reasoning: distinct from the
 * global and other module dashboards, every figure a real query.
 */
class HrDashboardService
{
    public function summary(): array
    {
        $today = now()->toDateString();

        return [
            'employee_counts' => [
                'active' => Employee::where('employment_status', Employee::STATUS_ACTIVE)->count(),
                'on_leave' => Employee::where('employment_status', Employee::STATUS_ON_LEAVE)->count(),
                'terminated' => Employee::where('employment_status', Employee::STATUS_TERMINATED)->count(),
                'resigned' => Employee::where('employment_status', Employee::STATUS_RESIGNED)->count(),
            ],
            'attendance_today' => [
                'present' => Attendance::where('date', $today)->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])->count(),
                'absent' => Attendance::where('date', $today)->where('status', Attendance::STATUS_ABSENT)->count(),
                'on_leave' => Attendance::where('date', $today)->where('status', Attendance::STATUS_ON_LEAVE)->count(),
                'not_marked' => Employee::where('employment_status', Employee::STATUS_ACTIVE)->count()
                    - Attendance::where('date', $today)->count(),
            ],
            'pending_leave_requests' => LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)->count(),
            'latest_payroll_run' => PayrollRun::orderByDesc('period_year')->orderByDesc('period_month')->first(['id', 'run_number', 'status', 'total_net', 'period_month', 'period_year']),
        ];
    }
}
