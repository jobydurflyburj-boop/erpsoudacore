<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    protected string $modelClass = Company::class;

    protected array $allowedFilters = ['is_default'];

    protected array $allowedSorts = ['created_at', 'legal_name'];

    protected array $searchableFields = ['legal_name', 'trade_name', 'cr_number', 'vat_number'];
}
