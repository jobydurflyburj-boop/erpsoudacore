<?php
namespace App\Repositories\Eloquent;
use App\Models\SalesOrder;
use App\Repositories\Contracts\SalesOrderRepositoryInterface;
class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    protected string $modelClass = SalesOrder::class;
    protected array $allowedFilters = ['status', 'customer_id'];
    protected array $allowedSorts = ['created_at', 'document_date', 'total', 'document_number'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
