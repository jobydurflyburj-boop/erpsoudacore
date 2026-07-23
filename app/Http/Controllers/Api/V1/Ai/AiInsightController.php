<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ReportSummaryRequest;
use App\Services\AiInsightService;
use Illuminate\Http\Request;

class AiInsightController extends Controller
{
    public function __construct(private readonly AiInsightService $insights) {}

    public function dashboard(Request $request) { return $this->ok($this->insights->dashboardInsight($request->user())); }
    public function sales(Request $request) { return $this->ok($this->insights->salesInsight($request->user())); }
    public function inventory(Request $request) { return $this->ok($this->insights->inventoryInsight($request->user())); }
    public function financial(Request $request) { return $this->ok($this->insights->financialInsight($request->user())); }
    public function crm(Request $request) { return $this->ok($this->insights->crmInsight($request->user())); }

    public function reportSummary(ReportSummaryRequest $request)
    {
        $v = $request->validated();
        return $this->ok($this->insights->reportSummary($request->user(), $v['report_label'], $v['data']));
    }
}
