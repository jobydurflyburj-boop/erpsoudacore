<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = QueryBuilder::for(ActivityLog::class)
            ->allowedFilters(['event', 'module', 'user_id'])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->with('user')
            ->paginate((int) $request->integer('page_size', 25));

        return ActivityLogResource::collection($logs);
    }

    /** Streamed CSV export — the "Export" action the Activity Log screen calls for. Capped to the most recent 5,000 rows so an unbounded tenant history can't turn this into a multi-minute request. */
    public function export(Request $request): StreamedResponse
    {
        $logs = QueryBuilder::for(ActivityLog::class)
            ->allowedFilters(['event', 'module', 'user_id'])
            ->defaultSort('-created_at')
            ->with('user')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'User', 'Module', 'Event', 'Description', 'IP Address', 'Browser']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at?->toIso8601String(),
                    $log->user?->email ?? 'System',
                    $log->module,
                    $log->event,
                    $log->description,
                    $log->ip_address,
                    $log->browser,
                ]);
            }

            fclose($out);
        }, 'activity-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
