<?php

namespace App\Repositories\Eloquent;

use App\Models\LeadStatus;
use App\Models\Tenant;
use App\Repositories\Contracts\LeadStatusRepositoryInterface;

class LeadStatusRepository extends BaseRepository implements LeadStatusRepositoryInterface
{
    protected string $modelClass = LeadStatus::class;

    protected array $allowedFilters = ['is_active', 'is_won', 'is_lost'];

    protected array $allowedSorts = ['sort_order', 'name_en', 'created_at'];

    protected array $searchableFields = ['name_en', 'name_ar'];

    protected string $defaultSort = 'sort_order';

    public function defaultFor(Tenant $tenant): ?LeadStatus
    {
        return LeadStatus::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->first();
    }
}
