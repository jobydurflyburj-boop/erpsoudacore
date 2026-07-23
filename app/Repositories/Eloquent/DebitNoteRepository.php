<?php
namespace App\Repositories\Eloquent;
use App\Models\DebitNote;
use App\Repositories\Contracts\DebitNoteRepositoryInterface;
class DebitNoteRepository extends BaseRepository implements DebitNoteRepositoryInterface
{
    protected string $modelClass = DebitNote::class;
    protected array $allowedFilters = ['status', 'supplier_id', 'supplier_bill_id'];
    protected array $allowedSorts = ['created_at', 'total'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
