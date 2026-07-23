<?php
namespace App\Repositories\Eloquent;
use App\Models\JournalEntry;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
class JournalEntryRepository extends BaseRepository implements JournalEntryRepositoryInterface
{
    protected string $modelClass = JournalEntry::class;
    protected array $allowedFilters = [];
    protected array $allowedSorts = ['created_at', 'entry_date', 'entry_number'];
    protected array $searchableFields = ['entry_number', 'memo'];
    protected string $defaultSort = '-entry_date';
}
