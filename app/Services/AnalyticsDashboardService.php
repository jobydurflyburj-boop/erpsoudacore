<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\JournalEntryLine;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;

/**
 * The one dashboard every prior sprint's own module dashboard (Sales,
 * Purchase, HR) deliberately did NOT try to be: a real cross-module
 * snapshot of the business as a whole, pulling one or two headline
 * figures from each audited module rather than duplicating any
 * module's own dashboard in full. `kpiSummary()` adds real
 * period-over-period comparison on top of that snapshot — the
 * "KPI Dashboard" requested separately from the Executive Dashboard,
 * distinguished by trend rather than by different data.
 */
class AnalyticsDashboardService
{
    public function executiveSummary(): array
    {
        $monthStart = now()->startOfMonth();

        return [
            'cash_position' => $this->accountBalance('1000'),
            'accounts_receivable' => $this->accountBalance('1100'),
            'accounts_payable' => $this->accountBalance('2000', credit: true),
            'sales_this_month' => (float) SalesInvoice::where('status', '!=', 'cancelled')
                ->whereBetween('document_date', [$monthStart, now()])->sum('total'),
            'purchases_this_month' => (float) SupplierBill::where('status', '!=', 'cancelled')
                ->whereBetween('document_date', [$monthStart, now()])->sum('total'),
            'open_purchase_orders' => PurchaseOrder::where('status', PurchaseOrder::STATUS_SENT)->count(),
            'active_employees' => Employee::where('employment_status', Employee::STATUS_ACTIVE)->count(),
            'open_leads' => Lead::whereHas('status', fn ($q) => $q->where('is_won', false)->where('is_lost', false))->count(),
            'open_opportunity_value' => (float) Opportunity::whereHas('stage', fn ($q) => $q->where('is_won', false)->where('is_lost', false))->sum('amount'),
            'low_stock_products' => Product::where('is_active', true)->where('reorder_point', '>', 0)
                ->with('stockLevels')->get()->filter(fn (Product $p) => $p->isLowStock())->count(),
        ];
    }

    /**
     * Real month-over-month deltas, not just current-period totals —
     * what makes this a "KPI" view rather than a repeat of the
     * Executive Dashboard. Percentage change is null (not zero) when
     * the prior period had no activity, since a 0-to-N change isn't a
     * meaningful percentage.
     */
    public function kpiSummary(): array
    {
        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $revenueThisMonth = (float) SalesInvoice::where('status', '!=', 'cancelled')
            ->whereBetween('document_date', [$thisMonthStart, now()])->sum('total');
        $revenueLastMonth = (float) SalesInvoice::where('status', '!=', 'cancelled')
            ->whereBetween('document_date', [$lastMonthStart, $lastMonthEnd])->sum('total');

        $expensesThisMonth = (float) SupplierBill::where('status', '!=', 'cancelled')
            ->whereBetween('document_date', [$thisMonthStart, now()])->sum('total');
        $expensesLastMonth = (float) SupplierBill::where('status', '!=', 'cancelled')
            ->whereBetween('document_date', [$lastMonthStart, $lastMonthEnd])->sum('total');

        $newLeadsThisMonth = Lead::whereBetween('created_at', [$thisMonthStart, now()])->count();
        $newLeadsLastMonth = Lead::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'revenue' => $this->trend($revenueThisMonth, $revenueLastMonth),
            'purchase_spend' => $this->trend($expensesThisMonth, $expensesLastMonth),
            'new_leads' => $this->trend((float) $newLeadsThisMonth, (float) $newLeadsLastMonth),
            'headcount' => Employee::where('employment_status', Employee::STATUS_ACTIVE)->count(),
        ];
    }

    private function trend(float $current, float $previous): array
    {
        return [
            'current' => round($current, 2),
            'previous' => round($previous, 2),
            'change_percent' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 2) : null,
        ];
    }

    private function accountBalance(string $code, bool $credit = false): float
    {
        $account = ChartOfAccount::where('code', $code)->first();
        if (! $account) {
            return 0.0;
        }

        $sums = JournalEntryLine::where('account_id', $account->id)
            ->selectRaw('coalesce(sum(debit),0) as total_debit, coalesce(sum(credit),0) as total_credit')
            ->first();

        return $credit
            ? round((float) $sums->total_credit - (float) $sums->total_debit, 2)
            : round((float) $sums->total_debit - (float) $sums->total_credit, 2);
    }
}
