<?php
namespace App\Repositories\Eloquent;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    protected string $modelClass = LeaveRequest::class;
    protected array $allowedFilters = ['employee_id', 'status', 'leave_type_id'];
    protected array $allowedSorts = ['created_at', 'start_date'];
}
