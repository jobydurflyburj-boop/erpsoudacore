<?php
namespace App\Http\Controllers\Api\V1\Reports;
use App\Http\Controllers\Controller;
use App\Services\AnalyticsDashboardService;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsDashboardService $analytics) {}

    public function executiveSummary() { return $this->ok($this->analytics->executiveSummary()); }
    public function kpiSummary() { return $this->ok($this->analytics->kpiSummary()); }
}
