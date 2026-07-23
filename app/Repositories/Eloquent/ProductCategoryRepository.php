<?php
namespace App\Repositories\Eloquent;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
class ProductCategoryRepository extends BaseRepository implements ProductCategoryRepositoryInterface
{
    protected string $modelClass = ProductCategory::class;
    protected array $allowedFilters = ['is_active', 'parent_id'];
    protected array $allowedSorts = ['name_en', 'created_at'];
    protected array $searchableFields = ['name_en', 'name_ar'];
    protected string $defaultSort = 'name_en';
}
