<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return $this->ok([
            'widgets' => $this->dashboard->widgets($user),
            'charts' => $this->dashboard->charts(),
            'recent_activities' => ActivityLogResource::collection($this->dashboard->recentActivities($user)->load('user')),
            'quick_actions' => $this->dashboard->quickActions($user),
            'system_health' => $this->dashboard->systemHealth(),
        ]);
    }
}
