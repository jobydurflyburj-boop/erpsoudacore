<?php
namespace App\Repositories\Eloquent;
use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    protected string $modelClass = Brand::class;
    protected array $allowedFilters = ['is_active'];
    protected array $allowedSorts = ['name', 'created_at'];
    protected array $searchableFields = ['name'];
    protected string $defaultSort = 'name';
}
