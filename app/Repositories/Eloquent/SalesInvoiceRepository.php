<?php
namespace App\Repositories\Eloquent;
use App\Models\SalesInvoice;
use App\Repositories\Contracts\SalesInvoiceRepositoryInterface;
class SalesInvoiceRepository extends BaseRepository implements SalesInvoiceRepositoryInterface
{
    protected string $modelClass = SalesInvoice::class;
    protected array $allowedFilters = ['status', 'customer_id'];
    protected array $allowedSorts = ['created_at', 'document_date', 'due_date', 'total', 'document_number'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
