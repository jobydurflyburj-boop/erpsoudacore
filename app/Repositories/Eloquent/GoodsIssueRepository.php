<?php
namespace App\Repositories\Eloquent;
use App\Models\GoodsIssue;
use App\Repositories\Contracts\GoodsIssueRepositoryInterface;
class GoodsIssueRepository extends BaseRepository implements GoodsIssueRepositoryInterface
{
    protected string $modelClass = GoodsIssue::class;
    protected array $allowedFilters = ['status', 'warehouse_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
