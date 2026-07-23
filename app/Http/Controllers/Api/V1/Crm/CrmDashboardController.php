<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Services\CrmDashboardService;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    public function __construct(private readonly CrmDashboardService $dashboard) {}

    public function index(Request $request)
    {
        $summary = $this->dashboard->summary($request->user());
        $summary['recent_leads'] = LeadResource::collection($summary['recent_leads']);

        return $this->ok($summary);
    }
}
