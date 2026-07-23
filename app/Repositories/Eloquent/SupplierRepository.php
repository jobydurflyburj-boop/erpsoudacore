<?php
namespace App\Repositories\Eloquent;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    protected string $modelClass = Supplier::class;
    protected array $allowedFilters = ['is_active'];
    protected array $allowedSorts = ['created_at', 'name'];
    protected array $searchableFields = ['supplier_number', 'name', 'email', 'phone', 'vat_number'];
}
