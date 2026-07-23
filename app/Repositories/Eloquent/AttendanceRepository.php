<?php
namespace App\Repositories\Eloquent;
use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    protected string $modelClass = Attendance::class;
    protected array $allowedFilters = ['employee_id', 'status', 'date'];
    protected array $allowedSorts = ['date', 'created_at'];
    protected string $defaultSort = '-date';
}
