<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Collection;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    protected string $modelClass = Permission::class;

    protected array $allowedFilters = ['module'];

    protected array $allowedSorts = ['module', 'action'];

    public function allGroupedByModule(): Collection
    {
        return Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');
    }

    public function findManyByNames(array $names): Collection
    {
        return Permission::whereIn('name', $names)->get();
    }
}
