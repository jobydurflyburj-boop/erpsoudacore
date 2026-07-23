<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreCustomReportRequest;
use App\Http\Requests\Reports\UpdateCustomReportRequest;
use App\Http\Resources\CustomReportResource;
use App\Models\CustomReport;
use App\Repositories\Contracts\CustomReportRepositoryInterface;
use App\Services\CustomReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomReportController extends Controller
{
    public function __construct(
        private readonly CustomReportRepositoryInterface $reports,
        private readonly CustomReportService $service,
        private readonly \App\Services\ReportExportService $exporter,
    ) {}

    public function index(Request $request)
    {
        return CustomReportResource::collection($this->reports->paginate($request));
    }

    public function sources()
    {
        return $this->ok($this->service->sources());
    }

    public function store(StoreCustomReportRequest $request)
    {
        try {
            $report = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['definition' => $e->getMessage()]);
        }

        return $this->ok(new CustomReportResource($report), 201);
    }

    public function show(CustomReport $customReport)
    {
        return $this->ok(new CustomReportResource($customReport));
    }

    public function update(UpdateCustomReportRequest $request, CustomReport $customReport)
    {
        try {
            $customReport = $this->service->update($customReport, $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['definition' => $e->getMessage()]);
        }

        return $this->ok(new CustomReportResource($customReport));
    }

    public function destroy(CustomReport $customReport)
    {
        $this->reports->delete($customReport);

        return response()->json(null, 204);
    }

    public function run(Request $request, CustomReport $customReport)
    {
        $rows = $this->service->run($customReport);
        $format = $request->query('format', 'json');

        if ($format === 'json') {
            return $this->ok($rows);
        }

        $columns = $customReport->group_by ? [$customReport->group_by, 'total'] : $customReport->columns;
        $rows = array_map(fn ($r) => (array) $r, $rows);
        $filename = str($customReport->name)->slug();

        return match ($format) {
            'pdf' => response($this->exporter->toPdf($customReport->name, $columns, $rows), 200, [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]),
            'xlsx' => response($this->exporter->toXlsx($columns, $rows), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
            ]),
            default => response($this->exporter->toCsv($columns, $rows), 200, [
                'Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]),
        };
    }
}
