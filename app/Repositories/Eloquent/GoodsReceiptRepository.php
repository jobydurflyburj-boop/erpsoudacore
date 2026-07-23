<?php
namespace App\Repositories\Eloquent;
use App\Models\GoodsReceipt;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
class GoodsReceiptRepository extends BaseRepository implements GoodsReceiptRepositoryInterface
{
    protected string $modelClass = GoodsReceipt::class;
    protected array $allowedFilters = ['status', 'warehouse_id', 'supplier_id', 'purchase_order_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
