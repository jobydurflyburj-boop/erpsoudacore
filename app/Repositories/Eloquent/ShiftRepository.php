<?php
namespace App\Repositories\Eloquent;
use App\Models\Shift;
use App\Repositories\Contracts\ShiftRepositoryInterface;
class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
{
    protected string $modelClass = Shift::class;
    protected array $allowedFilters = ['is_active'];
    protected array $allowedSorts = ['created_at', 'name'];
    protected array $searchableFields = ['name'];
}
