<?php
namespace App\Repositories\Eloquent;
use App\Models\SalaryComponent;
use App\Repositories\Contracts\SalaryComponentRepositoryInterface;
class SalaryComponentRepository extends BaseRepository implements SalaryComponentRepositoryInterface
{
    protected string $modelClass = SalaryComponent::class;
    protected array $allowedFilters = ['is_active', 'type'];
    protected array $allowedSorts = ['created_at', 'name_en'];
    protected array $searchableFields = ['name_en', 'name_ar'];
}
