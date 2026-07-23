<?php

namespace App\Repositories\Eloquent;

use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;

class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    protected string $modelClass = Lead::class;

    protected array $allowedFilters = [
        'lead_status_id', 'lead_source_id', 'assigned_to_user_id',
        'priority', 'country', 'city',
    ];

    protected array $allowedSorts = ['created_at', 'expected_revenue', 'probability', 'priority', 'lead_number'];

    protected array $searchableFields = [
        'lead_number', 'company_name', 'first_name', 'last_name',
        'arabic_name', 'email', 'phone', 'whatsapp',
    ];

    protected string $defaultSort = '-created_at';
}
