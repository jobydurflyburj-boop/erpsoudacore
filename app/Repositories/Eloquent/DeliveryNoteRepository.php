<?php
namespace App\Repositories\Eloquent;
use App\Models\DeliveryNote;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
class DeliveryNoteRepository extends BaseRepository implements DeliveryNoteRepositoryInterface
{
    protected string $modelClass = DeliveryNote::class;
    protected array $allowedFilters = ['status', 'customer_id'];
    protected array $allowedSorts = ['created_at', 'document_date'];
    protected array $searchableFields = ['document_number'];
    protected string $defaultSort = '-created_at';
}
