<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadSourceRequest;
use App\Http\Requests\Crm\UpdateLeadSourceRequest;
use App\Http\Resources\LeadSourceResource;
use App\Models\LeadSource;
use App\Repositories\Contracts\LeadSourceRepositoryInterface;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    public function __construct(private readonly LeadSourceRepositoryInterface $sources) {}

    public function index(Request $request)
    {
        return LeadSourceResource::collection($this->sources->paginate($request));
    }

    public function store(StoreLeadSourceRequest $request)
    {
        $source = $this->sources->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
        ]));

        return $this->ok(new LeadSourceResource($source), 201);
    }

    public function show(LeadSource $leadSource)
    {
        return $this->ok(new LeadSourceResource($leadSource));
    }

    public function update(UpdateLeadSourceRequest $request, LeadSource $leadSource)
    {
        return $this->ok(new LeadSourceResource($this->sources->update($leadSource, $request->validated())));
    }

    public function destroy(LeadSource $leadSource)
    {
        // LeadSource is soft-deletable, so the leads.lead_source_id FK's
        // ON DELETE behavior never actually fires here (a soft delete is
        // an UPDATE, not a DELETE) — this check is the real guard, not a
        // caught DB exception that would never trigger.
        if ($leadSource->leads()->exists()) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'This lead source is still assigned to one or more leads — deactivate it instead of deleting, or reassign those leads first.',
                'details' => (object) [],
            ], 409);
        }

        $this->sources->delete($leadSource);

        return response()->json(null, 204);
    }
}
