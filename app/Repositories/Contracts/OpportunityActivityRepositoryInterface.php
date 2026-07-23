<?php

namespace App\Repositories\Contracts;

use App\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface OpportunityActivityRepositoryInterface
{
    public function timelineFor(Opportunity $opportunity, Request $request): LengthAwarePaginator;
}
