<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\PlatformMetricsService;

class PlatformMetricsController extends Controller
{
    public function __construct(private readonly PlatformMetricsService $metrics) {}

    public function index()
    {
        return $this->ok($this->metrics->summary());
    }
}
