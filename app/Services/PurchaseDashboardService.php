<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

/**
 * A dedicated Purchase module dashboard — mirrors SalesDashboardService
 * exactly, same reasoning: distinct from the global and CRM dashboards,
 * every figure a real query.
 */
class PurchaseDashboardService
{
    public function summary(): array
    {
        return [
            'document_counts' => [
                'purchase_orders' => PurchaseOrder::count(),
                'goods_receipts' => GoodsReceipt::count(),
                'supplier_bills' => SupplierBill::count(),
                'debit_notes' => DebitNote::count(),
                'purchase_returns' => PurchaseReturn::count(),
            ],
            'spend_this_month' => (float) SupplierBill::where('status', '!=', 'cancelled')
                ->whereBetween('document_date', [now()->startOfMonth(), now()])->sum('total'),
            'payments_this_month' => (float) SupplierPayment::whereBetween('payment_date', [now()->startOfMonth(), now()])->sum('amount'),
            'outstanding_payables' => (float) SupplierBill::whereIn('status', ['approved', 'partial', 'overdue'])
                ->sum(DB::raw('total - paid_amount - credited_amount')),
            'overdue_bills' => SupplierBill::whereIn('status', ['approved', 'partial'])
                ->where('due_date', '<', now()->toDateString())->count(),
        ];
    }
}
