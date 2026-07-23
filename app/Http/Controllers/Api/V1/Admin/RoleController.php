<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignPermissionsRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly RoleService $roleService,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->roles->paginate($request);

        return RoleResource::collection($paginated->load('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $role = $this->roleService->createCustomRole(
                $request->user()->tenant,
                $request->string('code'),
                $request->string('name_en'),
                $request->input('name_ar'),
                $request->input('permissions', [])
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        return $this->ok(new RoleResource($role->load('permissions')), 201);
    }

    public function show(Role $role)
    {
        return $this->ok(new RoleResource($role->load('permissions')));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name_en' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return $this->ok(new RoleResource($this->roleService->updateRole($role, $data)));
    }

    public function assignPermissions(AssignPermissionsRequest $request, Role $role)
    {
        return $this->ok(new RoleResource($this->roleService->assignPermissions($role, $request->validated('permissions'))));
    }

    public function destroy(Role $role)
    {
        try {
            $this->roleService->deleteRole($role);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'conflict', 'message' => $e->getMessage(), 'details' => (object) []], 409);
        }

        return response()->json(null, 204);
    }
}
