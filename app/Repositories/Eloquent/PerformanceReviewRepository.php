<?php
namespace App\Repositories\Eloquent;
use App\Models\PerformanceReview;
use App\Repositories\Contracts\PerformanceReviewRepositoryInterface;
class PerformanceReviewRepository extends BaseRepository implements PerformanceReviewRepositoryInterface
{
    protected string $modelClass = PerformanceReview::class;
    protected array $allowedFilters = ['cycle_id', 'employee_id', 'status'];
    protected array $allowedSorts = ['created_at'];
}
