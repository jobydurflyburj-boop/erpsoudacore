<?php
namespace App\Repositories\Eloquent;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    protected string $modelClass = Product::class;
    protected array $allowedFilters = ['is_active', 'category'];
    protected array $allowedSorts = ['created_at', 'name_en', 'sku', 'sale_price'];
    protected array $searchableFields = ['sku', 'name_en', 'name_ar', 'category'];
    protected string $defaultSort = 'name_en';
}
