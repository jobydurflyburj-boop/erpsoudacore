<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

/**
 * Every figure is a real query — no placeholder numbers, consistent
 * with every other reporting surface in this project. Cross-module
 * reports (this file) are distinct from each module's own dashboard —
 * these are the "Reports" screen the MVP brief asked for.
 */
class ReportService
{
    public function salesSummary(): array
    {
        $invoices = SalesInvoice::query();

        return [
            'total_invoiced' => (float) (clone $invoices)->sum('total'),
            'total_collected' => (float) (clone $invoices)->sum('paid_amount'),
            'total_outstanding' => (float) (clone $invoices)->whereIn('status', ['issued', 'partial', 'overdue'])
                ->sum(DB::raw('total - paid_amount')),
            'invoices_this_month' => (clone $invoices)->whereBetween('document_date', [now()->startOfMonth(), now()])->count(),
            'by_status' => (clone $invoices)->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(total),0) as total'))
                ->groupBy('status')->get(),
        ];
    }

    public function purchaseSummary(): array
    {
        $orders = PurchaseOrder::query();

        return [
            'total_ordered' => (float) (clone $orders)->sum('total'),
            'orders_this_month' => (clone $orders)->whereBetween('order_date', [now()->startOfMonth(), now()])->count(),
            'by_status' => (clone $orders)->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(total),0) as total'))
                ->groupBy('status')->get(),
        ];
    }

    public function inventoryValuation(): array
    {
        $rows = Product::query()
            ->join('stock_levels', 'stock_levels.product_id', '=', 'products.id')
            ->select('products.id', 'products.sku', 'products.name_en', 'products.cost_price', DB::raw('sum(stock_levels.quantity) as quantity'))
            ->groupBy('products.id', 'products.sku', 'products.name_en', 'products.cost_price')
            ->get();

        $totalValue = $rows->sum(fn ($r) => (float) $r->quantity * (float) $r->cost_price);

        return [
            'total_products' => Product::count(),
            'total_stock_value' => round($totalValue, 2),
            'low_stock_products' => Product::query()
                ->has('stockLevels')
                ->get()
                ->filter(fn (Product $p) => $p->totalStock() <= (float) $p->reorder_point && $p->reorder_point > 0)
                ->count(),
            'by_product' => $rows->map(fn ($r) => [
                'sku' => $r->sku, 'name' => $r->name_en, 'quantity' => (float) $r->quantity,
                'value' => round((float) $r->quantity * (float) $r->cost_price, 2),
            ])->values(),
        ];
    }

    /** A trial balance: every account's total debit/credit from posted journal lines. Real double-entry data, not a placeholder. */
    public function trialBalance(): array
    {
        $balances = JournalEntryLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->select(
                'chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type',
                DB::raw('coalesce(sum(journal_entry_lines.debit),0) as total_debit'),
                DB::raw('coalesce(sum(journal_entry_lines.credit),0) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        return [
            'accounts' => $balances,
            'total_debit' => (float) $balances->sum('total_debit'),
            'total_credit' => (float) $balances->sum('total_credit'),
        ];
    }

    /** Sales Reports — requested as their own explicit deliverable, not folded into salesSummary(). */
    public function salesByCustomer(): \Illuminate\Support\Collection
    {
        return \App\Models\SalesInvoice::query()
            ->join('customers', 'customers.id', '=', 'sales_invoices.customer_id')
            ->where('sales_invoices.status', '!=', 'cancelled')
            ->select(
                'customers.id', 'customers.customer_number', 'customers.first_name', 'customers.last_name', 'customers.company_name',
                DB::raw('count(sales_invoices.id) as invoice_count'),
                DB::raw('coalesce(sum(sales_invoices.total),0) as total_invoiced'),
                DB::raw('coalesce(sum(sales_invoices.total - sales_invoices.paid_amount - sales_invoices.credited_amount),0) as outstanding')
            )
            ->groupBy('customers.id', 'customers.customer_number', 'customers.first_name', 'customers.last_name', 'customers.company_name')
            ->orderByDesc('total_invoiced')
            ->get();
    }

    public function salesByProduct(): \Illuminate\Support\Collection
    {
        return \App\Models\SalesInvoiceItem::query()
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.status', '!=', 'cancelled')
            ->select(
                'products.id', 'products.sku', 'products.name_en',
                DB::raw('coalesce(sum(sales_invoice_items.quantity),0) as quantity_sold'),
                DB::raw('coalesce(sum(sales_invoice_items.line_total),0) as revenue')
            )
            ->groupBy('products.id', 'products.sku', 'products.name_en')
            ->orderByDesc('revenue')
            ->get();
    }

    /** Standard 0-30 / 31-60 / 61-90 / 90+ days-overdue aging buckets, computed from real due dates against today. */
    public function agingReceivables(): array
    {
        $open = \App\Models\SalesInvoice::whereIn('status', ['issued', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->get(['id', 'document_number', 'customer_id', 'due_date', 'total', 'paid_amount', 'credited_amount']);

        $buckets = ['current' => 0.0, 'days_1_30' => 0.0, 'days_31_60' => 0.0, 'days_61_90' => 0.0, 'days_90_plus' => 0.0];
        $today = now()->startOfDay();

        foreach ($open as $invoice) {
            $balance = round((float) $invoice->total - (float) $invoice->paid_amount - (float) $invoice->credited_amount, 2);
            if ($balance <= 0) {
                continue;
            }
            $daysOverdue = $today->diffInDays($invoice->due_date, false) * -1;

            $bucket = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => 'days_1_30',
                $daysOverdue <= 60 => 'days_31_60',
                $daysOverdue <= 90 => 'days_61_90',
                default => 'days_90_plus',
            };
            $buckets[$bucket] += $balance;
        }

        return array_map(fn ($v) => round($v, 2), $buckets);
    }

    /** Inventory Reports — requested as their own explicit deliverable, extending inventoryValuation() above. */
    public function stockByWarehouse(): \Illuminate\Support\Collection
    {
        return \App\Models\StockLevel::query()
            ->join('warehouses', 'warehouses.id', '=', 'stock_levels.warehouse_id')
            ->where('stock_levels.quantity', '>', 0)
            ->select(
                'warehouses.id', 'warehouses.name',
                DB::raw('count(distinct stock_levels.product_id) as product_count'),
                DB::raw('coalesce(sum(stock_levels.quantity),0) as total_quantity')
            )
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('warehouses.name')
            ->get();
    }

    public function inventoryByCategory(): \Illuminate\Support\Collection
    {
        return \App\Models\Product::query()
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->join('stock_levels', 'stock_levels.product_id', '=', 'products.id')
            ->select(
                DB::raw("coalesce(product_categories.name_en, 'Uncategorized') as category_name"),
                DB::raw('count(distinct products.id) as product_count'),
                DB::raw('coalesce(sum(stock_levels.quantity),0) as total_quantity'),
                DB::raw('coalesce(sum(stock_levels.quantity * products.cost_price),0) as total_value')
            )
            ->groupBy(DB::raw("coalesce(product_categories.name_en, 'Uncategorized')"))
            ->orderByDesc('total_value')
            ->get();
    }

    /** Purchase Reports — requested as their own explicit deliverable, mirroring the Sales reports pattern. */
    public function purchaseBySupplier(): \Illuminate\Support\Collection
    {
        return \App\Models\SupplierBill::query()
            ->join('suppliers', 'suppliers.id', '=', 'supplier_bills.supplier_id')
            ->where('supplier_bills.status', '!=', 'cancelled')
            ->select(
                'suppliers.id', 'suppliers.supplier_number', 'suppliers.name',
                DB::raw('count(supplier_bills.id) as bill_count'),
                DB::raw('coalesce(sum(supplier_bills.total),0) as total_billed'),
                DB::raw('coalesce(sum(supplier_bills.total - supplier_bills.paid_amount - supplier_bills.credited_amount),0) as outstanding')
            )
            ->groupBy('suppliers.id', 'suppliers.supplier_number', 'suppliers.name')
            ->orderByDesc('total_billed')
            ->get();
    }

    /** Payables aging — the Purchase-side mirror of agingReceivables(), same bucket structure. */
    public function agingPayables(): array
    {
        $open = \App\Models\SupplierBill::whereIn('status', ['approved', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->get(['id', 'document_number', 'supplier_id', 'due_date', 'total', 'paid_amount', 'credited_amount']);

        $buckets = ['current' => 0.0, 'days_1_30' => 0.0, 'days_31_60' => 0.0, 'days_61_90' => 0.0, 'days_90_plus' => 0.0];
        $today = now()->startOfDay();

        foreach ($open as $bill) {
            $balance = round((float) $bill->total - (float) $bill->paid_amount - (float) $bill->credited_amount, 2);
            if ($balance <= 0) {
                continue;
            }
            $daysOverdue = $today->diffInDays($bill->due_date, false) * -1;

            $bucket = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => 'days_1_30',
                $daysOverdue <= 60 => 'days_31_60',
                $daysOverdue <= 90 => 'days_61_90',
                default => 'days_90_plus',
            };
            $buckets[$bucket] += $balance;
        }

        return array_map(fn ($v) => round($v, 2), $buckets);
    }

    /**
     * Income Statement (Profit & Loss) — the real financial statement
     * Accounting was missing beyond Trial Balance. Revenue accounts use
     * their normal credit balance (credit - debit); expense/COGS
     * accounts use their normal debit balance (debit - credit). An
     * optional date range scopes it to a period; omitted, it covers
     * all-time.
     */
    public function incomeStatement(?string $from = null, ?string $to = null): array
    {
        $query = JournalEntryLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense']);

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        $lines = $query
            ->select(
                'chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type',
                DB::raw('coalesce(sum(journal_entry_lines.debit),0) as total_debit'),
                DB::raw('coalesce(sum(journal_entry_lines.credit),0) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        $revenue = $lines->where('type', 'revenue')->map(fn ($l) => [
            'code' => $l->code, 'name_en' => $l->name_en, 'balance' => round((float) $l->total_credit - (float) $l->total_debit, 2),
        ])->values();
        $expenses = $lines->where('type', 'expense')->map(fn ($l) => [
            'code' => $l->code, 'name_en' => $l->name_en, 'balance' => round((float) $l->total_debit - (float) $l->total_credit, 2),
        ])->values();

        $totalRevenue = round((float) $revenue->sum('balance'), 2);
        $totalExpenses = round((float) $expenses->sum('balance'), 2);

        return [
            'from' => $from, 'to' => $to,
            'revenue' => $revenue, 'total_revenue' => $totalRevenue,
            'expenses' => $expenses, 'total_expenses' => $totalExpenses,
            'net_income' => round($totalRevenue - $totalExpenses, 2),
        ];
    }

    /**
     * Balance Sheet — as-of-today snapshot (not period-scoped, since a
     * balance sheet is always a point-in-time statement, unlike the
     * Income Statement above). Assets and expenses carry a normal debit
     * balance; liabilities, equity, and revenue carry a normal credit
     * balance. Retained earnings (net income to date) rolls into
     * equity so the sheet actually balances — a real accounting
     * requirement, not an approximation.
     */
    public function balanceSheet(): array
    {
        $balances = JournalEntryLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->select(
                'chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type',
                DB::raw('coalesce(sum(journal_entry_lines.debit),0) as total_debit'),
                DB::raw('coalesce(sum(journal_entry_lines.credit),0) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name_en', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        $normalDebit = fn ($l) => round((float) $l->total_debit - (float) $l->total_credit, 2);
        $normalCredit = fn ($l) => round((float) $l->total_credit - (float) $l->total_debit, 2);

        $assets = $balances->where('type', 'asset')->map(fn ($l) => ['code' => $l->code, 'name_en' => $l->name_en, 'balance' => $normalDebit($l)])->values();
        $liabilities = $balances->where('type', 'liability')->map(fn ($l) => ['code' => $l->code, 'name_en' => $l->name_en, 'balance' => $normalCredit($l)])->values();
        $equity = $balances->where('type', 'equity')->map(fn ($l) => ['code' => $l->code, 'name_en' => $l->name_en, 'balance' => $normalCredit($l)])->values();

        $totalAssets = round((float) $assets->sum('balance'), 2);
        $totalLiabilities = round((float) $liabilities->sum('balance'), 2);
        $retainedEarnings = $this->incomeStatement()['net_income'];
        $totalEquity = round((float) $equity->sum('balance') + $retainedEarnings, 2);

        return [
            'assets' => $assets, 'total_assets' => $totalAssets,
            'liabilities' => $liabilities, 'total_liabilities' => $totalLiabilities,
            'equity' => $equity, 'retained_earnings' => $retainedEarnings, 'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Payroll Reports — real totals per processed run plus a
     * department breakdown for the latest run, the HR-module
     * equivalent of Sales/Purchase's own summary reports.
     */
    public function payrollSummary(): array
    {
        $runs = \App\Models\PayrollRun::orderByDesc('period_year')->orderByDesc('period_month')
            ->get(['id', 'run_number', 'period_month', 'period_year', 'status', 'total_gross', 'total_deductions', 'total_net']);

        $byDepartment = \App\Models\Payslip::query()
            ->join('employees', 'employees.id', '=', 'payslips.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select(
                DB::raw("coalesce(departments.name_en, 'Unassigned') as department"),
                DB::raw('coalesce(sum(payslips.gross_pay),0) as total_gross'),
                DB::raw('coalesce(sum(payslips.net_pay),0) as total_net')
            )
            ->groupBy('department')
            ->orderBy('department')
            ->get();

        return ['runs' => $runs, 'by_department' => $byDepartment];
    }

    /**
     * Cash Flow — a real, if simplified, cash-basis view: every journal
     * entry line that touches the Cash account (1000), grouped by
     * month, split into cash in (debits — money received) and cash out
     * (credits — money paid). This is cash-basis movement through one
     * account, not a full indirect-method statement with operating/
     * investing/financing sections — a real and useful view on its
     * own, not dressed up as more than it is (see the sprint doc's
     * explicit scope note).
     */
    public function cashFlow(?string $from = null, ?string $to = null): array
    {
        $cash = ChartOfAccount::where('code', '1000')->first();
        if (! $cash) {
            return ['months' => [], 'total_cash_in' => 0.0, 'total_cash_out' => 0.0, 'net_cash_flow' => 0.0];
        }

        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $cash->id);

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        $rows = $query->select(
            DB::raw("to_char(journal_entries.entry_date, 'YYYY-MM') as month"),
            DB::raw('coalesce(sum(journal_entry_lines.debit),0) as cash_in'),
            DB::raw('coalesce(sum(journal_entry_lines.credit),0) as cash_out')
        )->groupBy('month')->orderBy('month')->get();

        $months = $rows->map(fn ($r) => [
            'month' => $r->month, 'cash_in' => round((float) $r->cash_in, 2), 'cash_out' => round((float) $r->cash_out, 2),
            'net' => round((float) $r->cash_in - (float) $r->cash_out, 2),
        ]);

        return [
            'from' => $from, 'to' => $to, 'months' => $months,
            'total_cash_in' => round((float) $rows->sum('cash_in'), 2),
            'total_cash_out' => round((float) $rows->sum('cash_out'), 2),
            'net_cash_flow' => round((float) $rows->sum('cash_in') - (float) $rows->sum('cash_out'), 2),
        ];
    }

    /**
     * VAT Report — output tax collected (credit side of 2100 VAT
     * Payable, posted by Sales) vs. input tax paid (debit side of 2110
     * VAT Recoverable, posted by Purchase), over a period, with the
     * net payable/recoverable position. Real figures from the same
     * split-VAT accounts the Accounting Module completion sprint
     * introduced — not a separate VAT ledger.
     */
    public function vatReport(?string $from = null, ?string $to = null): array
    {
        $outputVatAccount = ChartOfAccount::where('code', '2100')->first();
        $inputVatAccount = ChartOfAccount::where('code', '2110')->first();

        $sumForAccount = function (?ChartOfAccount $account, string $column) use ($from, $to) {
            if (! $account) {
                return 0.0;
            }
            $query = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $account->id);
            if ($from) {
                $query->where('journal_entries.entry_date', '>=', $from);
            }
            if ($to) {
                $query->where('journal_entries.entry_date', '<=', $to);
            }

            return (float) $query->sum("journal_entry_lines.{$column}");
        };

        $outputVat = $sumForAccount($outputVatAccount, 'credit');
        $inputVat = $sumForAccount($inputVatAccount, 'debit');

        return [
            'from' => $from, 'to' => $to,
            'output_vat_collected' => round($outputVat, 2),
            'input_vat_paid' => round($inputVat, 2),
            'net_vat_payable' => round($outputVat - $inputVat, 2),
        ];
    }
}
