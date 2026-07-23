<?php
namespace App\Repositories\Eloquent;
use App\Models\ScheduledReport;
use App\Repositories\Contracts\ScheduledReportRepositoryInterface;
class ScheduledReportRepository extends BaseRepository implements ScheduledReportRepositoryInterface
{
    protected string $modelClass = ScheduledReport::class;
    protected array $allowedFilters = ['is_active', 'frequency'];
    protected array $allowedSorts = ['created_at', 'next_run_at'];
    protected array $searchableFields = ['name'];
}
