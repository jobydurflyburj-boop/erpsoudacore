<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\CrmReportService;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Real PDF/CSV/XLSX export — deliberately scoped to reports whose
 * shape is naturally tabular (a flat list of rows), where a generic
 * exporter can represent the data honestly. The statement-style
 * reports (Income Statement, Balance Sheet, Cash Flow, VAT Report)
 * have sectioned/nested shapes a flat exporter can't represent without
 * distorting them — they remain viewable via their own JSON endpoints,
 * and a tenant who wants one of those in PDF/CSV form can rebuild it
 * as a Custom Report against the underlying data. See the sprint doc's
 * explicit scope note.
 */
class ReportExportController extends Controller
{
    /** report key => [columns, callable returning rows] */
    private function reportMap(): array
    {
        return [
            'trial_balance' => [['code', 'name_en', 'type', 'total_debit', 'total_credit'], fn () => collect($this->reports->trialBalance()['accounts'])->toArray()],
            'sales_by_customer' => [['customer_number', 'company_name', 'first_name', 'last_name', 'invoice_count', 'total_invoiced', 'outstanding'], fn () => $this->reports->salesByCustomer()->toArray()],
            'sales_by_product' => [['sku', 'name_en', 'quantity_sold', 'revenue'], fn () => $this->reports->salesByProduct()->toArray()],
            'stock_by_warehouse' => [['name', 'product_count', 'total_quantity'], fn () => $this->reports->stockByWarehouse()->toArray()],
            'inventory_by_category' => [['category_name', 'product_count', 'total_quantity', 'total_value'], fn () => $this->reports->inventoryByCategory()->toArray()],
            'purchase_by_supplier' => [['name', 'bill_count', 'total_billed', 'outstanding'], fn () => $this->reports->purchaseBySupplier()->toArray()],
            'payroll_runs' => [['run_number', 'period_month', 'period_year', 'status', 'total_gross', 'total_net'], fn () => $this->reports->payrollSummary()['runs']->toArray()],
            'leads_by_source' => [['source', 'total'], fn () => $this->crmReports->leadsBySource()],
            'leads_by_status' => [['status', 'total'], fn () => $this->crmReports->leadsByStatus()],
            'opportunities_by_stage' => [['stage', 'total', 'total_amount'], fn () => $this->crmReports->opportunitiesByStage()],
        ];
    }

    public function __construct(
        private readonly ReportService $reports,
        private readonly CrmReportService $crmReports,
        private readonly ReportExportService $export,
    ) {}

    public function export(Request $request, string $reportKey)
    {
        $map = $this->reportMap();
        if (! isset($map[$reportKey])) {
            throw ValidationException::withMessages(['report' => "'{$reportKey}' is not an exportable report. Exportable reports: ".implode(', ', array_keys($map))]);
        }

        [$columns, $rowsFn] = $map[$reportKey];
        $rows = array_map(fn ($r) => (array) $r, $rowsFn());
        $format = $request->query('format', 'csv');

        return match ($format) {
            'pdf' => response($this->export->toPdf(str($reportKey)->headline(), $columns, $rows), 200, [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"{$reportKey}.pdf\"",
            ]),
            'xlsx' => response($this->export->toXlsx($columns, $rows), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$reportKey}.xlsx\"",
            ]),
            default => response($this->export->toCsv($columns, $rows), 200, [
                'Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$reportKey}.csv\"",
            ]),
        };
    }
}
