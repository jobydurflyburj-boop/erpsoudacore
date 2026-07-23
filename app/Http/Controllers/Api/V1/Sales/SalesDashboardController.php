<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Services\SalesDashboardService;

class SalesDashboardController extends Controller
{
    public function __construct(private readonly SalesDashboardService $dashboard) {}

    public function index()
    {
        return $this->ok($this->dashboard->summary());
    }
}
