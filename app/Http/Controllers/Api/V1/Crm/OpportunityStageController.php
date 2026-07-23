<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreOpportunityStageRequest;
use App\Http\Requests\Crm\UpdateOpportunityStageRequest;
use App\Http\Resources\OpportunityStageResource;
use App\Models\OpportunityStage;
use App\Repositories\Contracts\OpportunityStageRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityStageController extends Controller
{
    public function __construct(private readonly OpportunityStageRepositoryInterface $stages) {}

    public function index(Request $request)
    {
        return OpportunityStageResource::collection($this->stages->paginate($request));
    }

    public function store(StoreOpportunityStageRequest $request)
    {
        $stage = DB::transaction(function () use ($request) {
            if ($request->boolean('is_default')) {
                $this->clearExistingDefault($request->user()->tenant_id);
            }

            return $this->stages->create(array_merge($request->validated(), [
                'tenant_id' => $request->user()->tenant_id,
            ]));
        });

        return $this->ok(new OpportunityStageResource($stage), 201);
    }

    public function show(OpportunityStage $opportunityStage)
    {
        return $this->ok(new OpportunityStageResource($opportunityStage));
    }

    public function update(UpdateOpportunityStageRequest $request, OpportunityStage $opportunityStage)
    {
        $stage = DB::transaction(function () use ($request, $opportunityStage) {
            if ($request->boolean('is_default') && ! $opportunityStage->is_default) {
                $this->clearExistingDefault($request->user()->tenant_id);
            }

            return $this->stages->update($opportunityStage, $request->validated());
        });

        return $this->ok(new OpportunityStageResource($stage));
    }

    public function destroy(OpportunityStage $opportunityStage)
    {
        // Soft-deletable, same reasoning as LeadStatus/LeadSource — the
        // DB's restrictOnDelete FK never actually fires for a soft
        // delete, so this explicit check is the real guard.
        if ($opportunityStage->opportunities()->exists()) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'This stage is still assigned to one or more opportunities — deactivate it instead of deleting, or move those opportunities to another stage first.',
                'details' => (object) [],
            ], 409);
        }

        if ($opportunityStage->is_default) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'The default stage cannot be deleted — set another stage as default first.',
                'details' => (object) [],
            ], 409);
        }

        $this->stages->delete($opportunityStage);

        return response()->json(null, 204);
    }

    private function clearExistingDefault(string $tenantId): void
    {
        OpportunityStage::where('tenant_id', $tenantId)->where('is_default', true)->update(['is_default' => false]);
    }
}
