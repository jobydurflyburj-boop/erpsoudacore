<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use App\Models\LeadStatus;

interface LeadStatusRepositoryInterface extends RepositoryInterface
{
    public function defaultFor(Tenant $tenant): ?LeadStatus;
}
