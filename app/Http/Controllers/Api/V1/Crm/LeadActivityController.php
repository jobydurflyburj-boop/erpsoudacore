<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadActivityRequest;
use App\Http\Resources\LeadActivityResource;
use App\Models\Lead;
use App\Repositories\Contracts\LeadActivityRepositoryInterface;
use App\Services\LeadService;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function __construct(
        private readonly LeadActivityRepositoryInterface $activities,
        private readonly LeadService $leadService,
    ) {}

    public function index(Request $request, Lead $lead)
    {
        $this->authorize('view', $lead);

        return LeadActivityResource::collection($this->activities->timelineFor($lead, $request));
    }

    public function store(StoreLeadActivityRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $activity = $this->leadService->logManualActivity(
            $request->user(), $lead, $request->validated('type'), $request->validated('description')
        );

        return $this->ok(new LeadActivityResource($activity->load('user')), 201);
    }
}
