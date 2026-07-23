<?php

namespace App\Repositories\Eloquent;

use App\Models\LeadSource;
use App\Repositories\Contracts\LeadSourceRepositoryInterface;

class LeadSourceRepository extends BaseRepository implements LeadSourceRepositoryInterface
{
    protected string $modelClass = LeadSource::class;

    protected array $allowedFilters = ['is_active'];

    protected array $allowedSorts = ['sort_order', 'name_en', 'created_at'];

    protected array $searchableFields = ['name_en', 'name_ar'];

    protected string $defaultSort = 'sort_order';
}
