<?php
namespace App\Repositories\Eloquent;
use App\Models\CreditNote;
use App\Repositories\Contracts\CreditNoteRepositoryInterface;
class CreditNoteRepository extends BaseRepository implements CreditNoteRepositoryInterface
{
    protected string $modelClass = CreditNote::class;
    protected array $allowedFilters = ['status', 'customer_id', 'sales_invoice_id'];
    protected array $allowedSorts = ['created_at', 'total'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
