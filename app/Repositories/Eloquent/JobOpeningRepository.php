<?php
namespace App\Repositories\Eloquent;
use App\Models\JobOpening;
use App\Repositories\Contracts\JobOpeningRepositoryInterface;
class JobOpeningRepository extends BaseRepository implements JobOpeningRepositoryInterface
{
    protected string $modelClass = JobOpening::class;
    protected array $allowedFilters = ['status', 'department_id'];
    protected array $allowedSorts = ['created_at', 'posted_date'];
    protected array $searchableFields = ['title'];
}
