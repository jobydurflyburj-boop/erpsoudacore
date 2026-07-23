<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SuspendTenantRequest;
use App\Http\Resources\PlatformTenantResource;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\SuperAdminTenantService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PlatformTenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly SuperAdminTenantService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->tenants->paginate($request);
        $paginated->getCollection()->loadCount('users');

        return PlatformTenantResource::collection($paginated);
    }

    public function show(Tenant $tenant)
    {
        return $this->ok(new PlatformTenantResource($tenant->loadCount('users')->load('suspendedBy')));
    }

    public function suspend(SuspendTenantRequest $request, Tenant $tenant)
    {
        try {
            $tenant = $this->service->suspend($request->user(), $tenant, $request->validated('reason'));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['reason' => $e->getMessage()]);
        }

        return $this->ok(new PlatformTenantResource($tenant));
    }

    public function reactivate(Request $request, Tenant $tenant)
    {
        try {
            $tenant = $this->service->reactivate($request->user(), $tenant);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'conflict', 'message' => $e->getMessage(), 'details' => (object) []], 409);
        }

        return $this->ok(new PlatformTenantResource($tenant));
    }
}
