<?php
namespace App\Repositories\Eloquent;
use App\Models\Holiday;
use App\Repositories\Contracts\HolidayRepositoryInterface;
class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
{
    protected string $modelClass = Holiday::class;
    protected array $allowedFilters = ['is_recurring_annually'];
    protected array $allowedSorts = ['date', 'created_at'];
    protected array $searchableFields = ['name'];
    protected string $defaultSort = 'date';
}
