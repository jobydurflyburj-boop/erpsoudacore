<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadStatusRequest;
use App\Http\Requests\Crm\UpdateLeadStatusRequest;
use App\Http\Resources\LeadStatusResource;
use App\Models\LeadStatus;
use App\Repositories\Contracts\LeadStatusRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadStatusController extends Controller
{
    public function __construct(private readonly LeadStatusRepositoryInterface $statuses) {}

    public function index(Request $request)
    {
        return LeadStatusResource::collection($this->statuses->paginate($request));
    }

    public function store(StoreLeadStatusRequest $request)
    {
        $status = DB::transaction(function () use ($request) {
            if ($request->boolean('is_default')) {
                $this->clearExistingDefault($request->user()->tenant_id);
            }

            return $this->statuses->create(array_merge($request->validated(), [
                'tenant_id' => $request->user()->tenant_id,
            ]));
        });

        return $this->ok(new LeadStatusResource($status), 201);
    }

    public function show(LeadStatus $leadStatus)
    {
        return $this->ok(new LeadStatusResource($leadStatus));
    }

    public function update(UpdateLeadStatusRequest $request, LeadStatus $leadStatus)
    {
        $status = DB::transaction(function () use ($request, $leadStatus) {
            if ($request->boolean('is_default') && ! $leadStatus->is_default) {
                $this->clearExistingDefault($request->user()->tenant_id);
            }

            return $this->statuses->update($leadStatus, $request->validated());
        });

        return $this->ok(new LeadStatusResource($status));
    }

    public function destroy(LeadStatus $leadStatus)
    {
        // Same reasoning as LeadSourceController::destroy — LeadStatus is
        // soft-deletable, so the DB's restrictOnDelete FK never actually
        // fires; this explicit check is the real guard.
        if ($leadStatus->leads()->exists()) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'This status is still assigned to one or more leads — deactivate it instead of deleting, or move those leads to another status first.',
                'details' => (object) [],
            ], 409);
        }

        if ($leadStatus->is_default) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'The default status cannot be deleted — set another status as default first.',
                'details' => (object) [],
            ], 409);
        }

        $this->statuses->delete($leadStatus);

        return response()->json(null, 204);
    }

    /** Exactly one lead status may be `is_default` per tenant at a time — the status a new lead starts in when none is specified. */
    private function clearExistingDefault(string $tenantId): void
    {
        LeadStatus::where('tenant_id', $tenantId)->where('is_default', true)->update(['is_default' => false]);
    }
}
