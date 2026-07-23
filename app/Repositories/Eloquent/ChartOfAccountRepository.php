<?php
namespace App\Repositories\Eloquent;
use App\Models\ChartOfAccount;
use App\Repositories\Contracts\ChartOfAccountRepositoryInterface;
class ChartOfAccountRepository extends BaseRepository implements ChartOfAccountRepositoryInterface
{
    protected string $modelClass = ChartOfAccount::class;
    protected array $allowedFilters = ['type', 'is_active', 'parent_id'];
    protected array $allowedSorts = ['code', 'name_en'];
    protected array $searchableFields = ['code', 'name_en', 'name_ar'];
    protected string $defaultSort = 'code';
}
