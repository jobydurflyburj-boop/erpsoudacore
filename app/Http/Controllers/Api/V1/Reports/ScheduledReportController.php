<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreScheduledReportRequest;
use App\Http\Requests\Reports\UpdateScheduledReportRequest;
use App\Http\Resources\ScheduledReportResource;
use App\Models\ScheduledReport;
use App\Repositories\Contracts\ScheduledReportRepositoryInterface;
use App\Services\ScheduledReportService;
use Illuminate\Http\Request;

class ScheduledReportController extends Controller
{
    public function __construct(
        private readonly ScheduledReportRepositoryInterface $schedules,
        private readonly ScheduledReportService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->schedules->paginate($request);
        $paginated->getCollection()->load('customReport');

        return ScheduledReportResource::collection($paginated);
    }

    public function store(StoreScheduledReportRequest $request)
    {
        $schedule = $this->service->create($request->user(), $request->validated());

        return $this->ok(new ScheduledReportResource($schedule->load('customReport')), 201);
    }

    public function update(UpdateScheduledReportRequest $request, ScheduledReport $scheduledReport)
    {
        $scheduledReport = $this->service->update($scheduledReport, $request->validated());

        return $this->ok(new ScheduledReportResource($scheduledReport->load('customReport')));
    }

    public function destroy(ScheduledReport $scheduledReport)
    {
        $this->schedules->delete($scheduledReport);

        return response()->json(null, 204);
    }

    /** Forces this one schedule to run immediately by setting next_run_at to now, then processing due schedules for this tenant. */
    public function runNow(ScheduledReport $scheduledReport)
    {
        $this->schedules->update($scheduledReport, ['next_run_at' => now()]);
        $result = $this->service->process();

        return $this->ok($result);
    }
}
