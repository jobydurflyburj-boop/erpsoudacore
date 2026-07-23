<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreOpportunityActivityRequest;
use App\Http\Resources\OpportunityActivityResource;
use App\Models\Opportunity;
use App\Repositories\Contracts\OpportunityActivityRepositoryInterface;
use App\Services\OpportunityService;
use Illuminate\Http\Request;

class OpportunityActivityController extends Controller
{
    public function __construct(
        private readonly OpportunityActivityRepositoryInterface $activities,
        private readonly OpportunityService $opportunityService,
    ) {}

    public function index(Request $request, Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        return OpportunityActivityResource::collection($this->activities->timelineFor($opportunity, $request));
    }

    public function store(StoreOpportunityActivityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $activity = $this->opportunityService->logManualActivity(
            $request->user(), $opportunity, $request->validated('type'), $request->validated('description')
        );

        return $this->ok(new OpportunityActivityResource($activity->load('user')), 201);
    }
}
