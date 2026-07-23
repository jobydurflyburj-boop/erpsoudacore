<?php

namespace App\Repositories\Contracts;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface LeadActivityRepositoryInterface
{
    public function timelineFor(Lead $lead, Request $request): LengthAwarePaginator;
}
