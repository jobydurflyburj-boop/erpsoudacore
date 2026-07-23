<?php
namespace App\Repositories\Eloquent;
use App\Models\StockTransfer;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
class StockTransferRepository extends BaseRepository implements StockTransferRepositoryInterface
{
    protected string $modelClass = StockTransfer::class;
    protected array $allowedFilters = ['status', 'from_warehouse_id', 'to_warehouse_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
