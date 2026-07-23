<?php
namespace App\Repositories\Eloquent;
use App\Models\LeaveType;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    protected string $modelClass = LeaveType::class;
    protected array $allowedFilters = ['is_active', 'is_paid'];
    protected array $allowedSorts = ['created_at', 'name_en'];
    protected array $searchableFields = ['name_en', 'name_ar'];
}
