<?php

namespace App\Repositories\Contracts;

use App\Models\OpportunityStage;
use App\Models\Tenant;

interface OpportunityStageRepositoryInterface extends RepositoryInterface
{
    public function defaultFor(Tenant $tenant): ?OpportunityStage;
}
