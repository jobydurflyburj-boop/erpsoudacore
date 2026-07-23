<?php
namespace App\Repositories\Eloquent;
use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
class UnitRepository extends BaseRepository implements UnitRepositoryInterface
{
    protected string $modelClass = Unit::class;
    protected array $allowedFilters = ['is_active'];
    protected array $allowedSorts = ['code', 'created_at'];
    protected array $searchableFields = ['code', 'name_en'];
    protected string $defaultSort = 'code';
}
