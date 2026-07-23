<?php
namespace App\Repositories\Eloquent;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    protected string $modelClass = PurchaseOrder::class;
    protected array $allowedFilters = ['status', 'supplier_id'];
    protected array $allowedSorts = ['created_at', 'order_date', 'total', 'po_number'];
    protected array $searchableFields = ['po_number'];
    protected string $defaultSort = '-created_at';
}
