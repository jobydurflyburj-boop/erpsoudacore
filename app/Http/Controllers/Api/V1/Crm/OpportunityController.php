<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\AssignOpportunityRequest;
use App\Http\Requests\Crm\StoreOpportunityRequest;
use App\Http\Requests\Crm\UpdateOpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Repositories\Contracts\OpportunityRepositoryInterface;
use App\Services\OpportunityService;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly OpportunityService $opportunityService,
    ) {}

    public function index(Request $request)
    {
        $query = $this->opportunities->query()->with(['customer', 'stage', 'assignee']);

        if (in_array($request->user()->role?->code, Opportunity::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('assigned_to_user_id', $request->user()->id);
        }

        $paginated = \Spatie\QueryBuilder\QueryBuilder::for($query)
            ->allowedFilters(['stage_id', 'customer_id', 'assigned_to_user_id', 'priority'])
            ->allowedSorts(['created_at', 'amount', 'expected_close_date', 'probability', 'opportunity_number'])
            ->defaultSort('-created_at')
            ->paginate((int) $request->integer('page_size', 25));

        return OpportunityResource::collection($paginated);
    }

    public function store(StoreOpportunityRequest $request)
    {
        $opportunity = $this->opportunityService->create($request->user(), $request->validated());

        return $this->ok(new OpportunityResource($opportunity->load(['customer', 'stage', 'assignee'])), 201);
    }

    public function show(Request $request, Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        return $this->ok(new OpportunityResource($opportunity->load(['customer', 'stage', 'assignee', 'creator', 'updater'])));
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $opportunity = $this->opportunityService->update($request->user(), $opportunity, $request->validated());

        return $this->ok(new OpportunityResource($opportunity->load(['customer', 'stage', 'assignee'])));
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);

        $opportunity->delete();

        return response()->json(null, 204);
    }

    public function assign(AssignOpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('assign', $opportunity);

        $opportunity = $this->opportunityService->assign($request->user(), $opportunity, $request->validated('assigned_to_user_id'));

        return $this->ok(new OpportunityResource($opportunity->load(['customer', 'stage', 'assignee'])));
    }
}
