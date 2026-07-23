<?php

namespace App\Repositories\Eloquent;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Repositories\Contracts\LeadActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class LeadActivityRepository implements LeadActivityRepositoryInterface
{
    public function timelineFor(Lead $lead, Request $request): LengthAwarePaginator
    {
        return LeadActivity::where('lead_id', $lead->id)
            ->with('user')
            ->latest('created_at')
            ->paginate((int) $request->integer('page_size', 25));
    }
}
