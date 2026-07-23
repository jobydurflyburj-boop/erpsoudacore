<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Services\PurchaseDashboardService;

class PurchaseDashboardController extends Controller
{
    public function __construct(private readonly PurchaseDashboardService $dashboard) {}

    public function index()
    {
        return $this->ok($this->dashboard->summary());
    }
}
