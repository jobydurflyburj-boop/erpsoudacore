<?php
namespace App\Repositories\Eloquent;
use App\Models\SupplierBill;
use App\Repositories\Contracts\SupplierBillRepositoryInterface;
class SupplierBillRepository extends BaseRepository implements SupplierBillRepositoryInterface
{
    protected string $modelClass = SupplierBill::class;
    protected array $allowedFilters = ['status', 'supplier_id'];
    protected array $allowedSorts = ['created_at', 'total', 'due_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
