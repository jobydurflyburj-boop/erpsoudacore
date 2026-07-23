<?php
namespace App\Http\Controllers\Api\V1\Reports;
use App\Http\Controllers\Controller;
use App\Services\CrmReportService;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly CrmReportService $crmReports,
    ) {}

    public function sales() { return $this->ok($this->reports->salesSummary()); }
    public function purchases() { return $this->ok($this->reports->purchaseSummary()); }
    public function inventory() { return $this->ok($this->reports->inventoryValuation()); }
    public function trialBalance() { return $this->ok($this->reports->trialBalance()); }
    public function salesByCustomer() { return $this->ok($this->reports->salesByCustomer()); }
    public function salesByProduct() { return $this->ok($this->reports->salesByProduct()); }
    public function agingReceivables() { return $this->ok($this->reports->agingReceivables()); }
    public function stockByWarehouse() { return $this->ok($this->reports->stockByWarehouse()); }
    public function inventoryByCategory() { return $this->ok($this->reports->inventoryByCategory()); }
    public function purchaseBySupplier() { return $this->ok($this->reports->purchaseBySupplier()); }
    public function agingPayables() { return $this->ok($this->reports->agingPayables()); }
    public function incomeStatement(\Illuminate\Http\Request $request) { return $this->ok($this->reports->incomeStatement($request->query('from'), $request->query('to'))); }
    public function balanceSheet() { return $this->ok($this->reports->balanceSheet()); }
    public function payrollSummary() { return $this->ok($this->reports->payrollSummary()); }
    public function cashFlow(\Illuminate\Http\Request $request) { return $this->ok($this->reports->cashFlow($request->query('from'), $request->query('to'))); }
    public function vatReport(\Illuminate\Http\Request $request) { return $this->ok($this->reports->vatReport($request->query('from'), $request->query('to'))); }
    public function leadsBySource() { return $this->ok($this->crmReports->leadsBySource()); }
    public function leadsByStatus() { return $this->ok($this->crmReports->leadsByStatus()); }
    public function opportunitiesByStage() { return $this->ok($this->crmReports->opportunitiesByStage()); }
    public function conversionFunnel() { return $this->ok($this->crmReports->conversionFunnel()); }
}
