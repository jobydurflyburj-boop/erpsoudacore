<?php
namespace App\Repositories\Eloquent;
use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
class StockAdjustmentRepository extends BaseRepository implements StockAdjustmentRepositoryInterface
{
    protected string $modelClass = StockAdjustment::class;
    protected array $allowedFilters = ['status', 'warehouse_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
