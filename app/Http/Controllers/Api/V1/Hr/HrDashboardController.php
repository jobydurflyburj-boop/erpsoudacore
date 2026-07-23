<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Services\HrDashboardService;

class HrDashboardController extends Controller
{
    public function __construct(private readonly HrDashboardService $dashboard) {}

    public function summary()
    {
        return $this->ok($this->dashboard->summary());
    }
}
