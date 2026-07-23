<?php

namespace App\Repositories\Eloquent;

use App\Models\Opportunity;
use App\Repositories\Contracts\OpportunityRepositoryInterface;

class OpportunityRepository extends BaseRepository implements OpportunityRepositoryInterface
{
    protected string $modelClass = Opportunity::class;

    protected array $allowedFilters = ['stage_id', 'customer_id', 'assigned_to_user_id', 'priority'];

    protected array $allowedSorts = ['created_at', 'amount', 'expected_close_date', 'probability', 'opportunity_number'];

    protected array $searchableFields = ['opportunity_number', 'name'];

    protected string $defaultSort = '-created_at';
}
