<?php
namespace App\Repositories\Eloquent;
use App\Models\JobApplication;
use App\Repositories\Contracts\JobApplicationRepositoryInterface;
class JobApplicationRepository extends BaseRepository implements JobApplicationRepositoryInterface
{
    protected string $modelClass = JobApplication::class;
    protected array $allowedFilters = ['job_opening_id', 'candidate_id', 'status'];
    protected array $allowedSorts = ['created_at', 'applied_at'];
}
