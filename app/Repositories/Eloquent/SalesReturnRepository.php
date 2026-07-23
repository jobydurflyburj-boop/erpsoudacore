<?php
namespace App\Repositories\Eloquent;
use App\Models\SalesReturn;
use App\Repositories\Contracts\SalesReturnRepositoryInterface;
class SalesReturnRepository extends BaseRepository implements SalesReturnRepositoryInterface
{
    protected string $modelClass = SalesReturn::class;
    protected array $allowedFilters = ['status', 'customer_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
