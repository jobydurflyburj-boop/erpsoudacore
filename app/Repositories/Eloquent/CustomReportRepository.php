<?php
namespace App\Repositories\Eloquent;
use App\Models\CustomReport;
use App\Repositories\Contracts\CustomReportRepositoryInterface;
class CustomReportRepository extends BaseRepository implements CustomReportRepositoryInterface
{
    protected string $modelClass = CustomReport::class;
    protected array $allowedFilters = ['source'];
    protected array $allowedSorts = ['created_at', 'name'];
    protected array $searchableFields = ['name', 'description'];
}
