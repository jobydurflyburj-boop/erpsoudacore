<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    protected string $modelClass = Department::class;

    protected array $allowedFilters = ['company_id', 'is_active', 'manager_user_id'];

    protected array $allowedSorts = ['created_at', 'name_en'];

    protected array $searchableFields = ['name_en', 'name_ar'];
}
