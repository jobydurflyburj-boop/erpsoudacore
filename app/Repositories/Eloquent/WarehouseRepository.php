<?php
namespace App\Repositories\Eloquent;
use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    protected string $modelClass = Warehouse::class;
    protected array $allowedFilters = ['is_active', 'branch_id'];
    protected array $allowedSorts = ['created_at', 'name'];
    protected array $searchableFields = ['name'];
}
