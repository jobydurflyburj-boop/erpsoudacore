<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CustomerPayment;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

/**
 * A dedicated Sales module dashboard — distinct from the global
 * Platform Admin dashboard and the CRM dashboard. Every figure is a
 * real query against the sales tables built this sprint.
 */
class SalesDashboardService
{
    public function summary(): array
    {
        return [
            'document_counts' => [
                'quotations' => Quotation::count(),
                'sales_orders' => SalesOrder::count(),
                'invoices' => SalesInvoice::count(),
                'credit_notes' => CreditNote::count(),
                'sales_returns' => SalesReturn::count(),
            ],
            'quotation_conversion_rate' => $this->quotationConversionRate(),
            'revenue_this_month' => (float) SalesInvoice::where('status', '!=', 'cancelled')
                ->whereBetween('document_date', [now()->startOfMonth(), now()])->sum('total'),
            'payments_this_month' => (float) CustomerPayment::whereBetween('payment_date', [now()->startOfMonth(), now()])->sum('amount'),
            'outstanding_receivables' => (float) SalesInvoice::whereIn('status', ['issued', 'partial', 'overdue'])
                ->sum(DB::raw('total - paid_amount - credited_amount')),
            'overdue_invoices' => SalesInvoice::whereIn('status', ['issued', 'partial'])
                ->where('due_date', '<', now()->toDateString())->count(),
        ];
    }

    private function quotationConversionRate(): float
    {
        $total = Quotation::whereIn('status', ['accepted', 'rejected', 'expired'])->count();
        $accepted = Quotation::where('status', 'accepted')->count();

        return $total > 0 ? round(($accepted / $total) * 100, 1) : 0.0;
    }
}
