<?php
namespace App\Repositories\Eloquent;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    protected string $modelClass = Employee::class;
    protected array $allowedFilters = ['employment_status', 'department_id', 'designation_id'];
    protected array $allowedSorts = ['created_at', 'hire_date', 'full_name'];
    protected array $searchableFields = ['employee_number', 'full_name', 'email', 'phone'];
}
