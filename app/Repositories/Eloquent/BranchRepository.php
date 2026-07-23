<?php

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    protected string $modelClass = Branch::class;

    protected array $allowedFilters = ['company_id', 'is_active', 'manager_user_id'];

    protected array $allowedSorts = ['created_at', 'name'];

    protected array $searchableFields = ['name', 'city'];
}
