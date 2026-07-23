<?php
namespace App\Repositories\Eloquent;
use App\Models\PerformanceReviewCycle;
use App\Repositories\Contracts\PerformanceReviewCycleRepositoryInterface;
class PerformanceReviewCycleRepository extends BaseRepository implements PerformanceReviewCycleRepositoryInterface
{
    protected string $modelClass = PerformanceReviewCycle::class;
    protected array $allowedFilters = ['status'];
    protected array $allowedSorts = ['created_at', 'period_start'];
    protected array $searchableFields = ['name'];
}
