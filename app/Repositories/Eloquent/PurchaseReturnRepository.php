<?php
namespace App\Repositories\Eloquent;
use App\Models\PurchaseReturn;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
class PurchaseReturnRepository extends BaseRepository implements PurchaseReturnRepositoryInterface
{
    protected string $modelClass = PurchaseReturn::class;
    protected array $allowedFilters = ['status', 'supplier_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
