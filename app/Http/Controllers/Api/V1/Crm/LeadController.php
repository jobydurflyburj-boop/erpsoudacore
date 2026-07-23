<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\AssignLeadRequest;
use App\Http\Requests\Crm\ConvertLeadRequest;
use App\Http\Requests\Crm\StoreLeadRequest;
use App\Http\Requests\Crm\UpdateLeadRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
        private readonly LeadService $leadService,
        private readonly LeadConversionService $conversionService,
    ) {}

    public function index(Request $request)
    {
        $query = $this->leads->query()->with(['source', 'status', 'assignee'])->withCount('attachments');

        // Record-level scoping (LeadPolicy mirrors this for a single
        // record) — Sales sees only their own assigned leads in the list
        // too, not just when loading one directly by ID.
        if (in_array($request->user()->role?->code, Lead::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('assigned_to_user_id', $request->user()->id);
        }

        $paginated = $this->applyQueryParams($query, $request);

        return LeadResource::collection($paginated);
    }

    public function store(StoreLeadRequest $request)
    {
        $lead = $this->leadService->create($request->user(), $request->validated());

        return $this->ok(new LeadResource($lead->load(['source', 'status', 'assignee'])), 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorize('view', $lead);

        return $this->ok(new LeadResource($lead->load(['source', 'status', 'assignee', 'creator', 'updater'])->loadCount('attachments')));
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $lead = $this->leadService->update($request->user(), $lead, $request->validated());

        return $this->ok(new LeadResource($lead->load(['source', 'status', 'assignee'])));
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return response()->json(null, 204);
    }

    public function assign(AssignLeadRequest $request, Lead $lead)
    {
        $this->authorize('assign', $lead);

        $lead = $this->leadService->assign($request->user(), $lead, $request->validated('assigned_to_user_id'));

        return $this->ok(new LeadResource($lead->load(['source', 'status', 'assignee'])));
    }

    public function convertToCustomer(ConvertLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        try {
            $customer = $this->conversionService->convert($request->user(), $lead, $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['lead' => $e->getMessage()]);
        }

        return $this->ok(new CustomerResource($customer->load('accountManager')), 201);
    }

    /**
     * Applies filtering/sorting/searching consistent with every other
     * paginated list in this codebase (spatie/laravel-query-builder),
     * layered on top of the already-scoped $query builder above rather
     * than going through BaseRepository::paginate() directly — that
     * method builds its own fresh QueryBuilder::for($modelClass) and
     * would lose the ownership scoping applied in index().
     */
    private function applyQueryParams($query, Request $request)
    {
        return \Spatie\QueryBuilder\QueryBuilder::for($query)
            ->allowedFilters(['lead_status_id', 'lead_source_id', 'assigned_to_user_id', 'priority', 'country', 'city'])
            ->allowedSorts(['created_at', 'expected_revenue', 'probability', 'priority', 'lead_number'])
            ->defaultSort('-created_at')
            ->paginate((int) $request->integer('page_size', 25));
    }
}
