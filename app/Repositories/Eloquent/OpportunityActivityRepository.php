<?php

namespace App\Repositories\Eloquent;

use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Repositories\Contracts\OpportunityActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class OpportunityActivityRepository implements OpportunityActivityRepositoryInterface
{
    public function timelineFor(Opportunity $opportunity, Request $request): LengthAwarePaginator
    {
        return OpportunityActivity::where('opportunity_id', $opportunity->id)
            ->with('user')
            ->latest('created_at')
            ->paginate((int) $request->integer('page_size', 25));
    }
}
