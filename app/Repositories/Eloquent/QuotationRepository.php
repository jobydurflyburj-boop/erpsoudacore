<?php
namespace App\Repositories\Eloquent;
use App\Models\Quotation;
use App\Repositories\Contracts\QuotationRepositoryInterface;
class QuotationRepository extends BaseRepository implements QuotationRepositoryInterface
{
    protected string $modelClass = Quotation::class;
    protected array $allowedFilters = ['status', 'customer_id'];
    protected array $allowedSorts = ['created_at', 'document_date', 'total', 'document_number'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
