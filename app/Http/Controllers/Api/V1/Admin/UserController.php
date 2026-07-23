<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $userService,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->users->paginate($request);

        return UserResource::collection($paginated->load(['role', 'department']));
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->invite($request->user()->tenant, $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        return $this->ok(new UserResource($user->load('role')), 201);
    }

    public function show(User $user)
    {
        return $this->ok(new UserResource($user->load(['role', 'department', 'branches'])));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->ok(new UserResource($this->userService->updateProfile($user, $request->validated())));
    }

    public function changeRole(Request $request, User $user)
    {
        $request->validate(['role_id' => ['required', 'uuid', 'exists:roles,id']]);

        return $this->ok(new UserResource($this->userService->changeRole($user, $request->string('role_id'))));
    }

    public function setStatus(Request $request, User $user)
    {
        $request->validate(['status' => ['required', 'in:active,disabled']]);

        return $this->ok(new UserResource($this->userService->setStatus($user, $request->string('status'))));
    }

    public function resetPassword(User $user)
    {
        $this->userService->adminResetPassword($user);

        return $this->ok(['message' => 'A password reset email has been sent to the user.']);
    }

    public function assignBranches(Request $request, User $user)
    {
        $tenantId = $request->user()->tenant_id;

        $data = $request->validate([
            'branch_ids' => ['required', 'array'],
            'branch_ids.*' => ['uuid', \Illuminate\Validation\Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
        ]);

        return $this->ok(new UserResource($this->userService->assignBranches($user, $data['branch_ids'])->load(['role', 'department', 'branches'])));
    }

    public function destroy(User $user)
    {
        $user->delete(); // soft delete

        return response()->json(null, 204);
    }
}
