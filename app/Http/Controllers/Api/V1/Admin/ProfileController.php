<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function show(Request $request)
    {
        return $this->ok(new UserResource($request->user()->load(['role.permissions', 'company', 'department', 'defaultBranch', 'branches'])));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'preferred_locale' => ['sometimes', 'in:ar,en'],
            'timezone' => ['sometimes', 'string', 'max:64'],
        ]);

        return $this->ok(new UserResource($this->userService->updateProfile($request->user(), $data)));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);

        $path = $request->file('avatar')->store("tenants/{$request->user()->tenant_id}/avatars", 'public');

        return $this->ok(new UserResource($this->userService->updateAvatar($request->user(), $path)));
    }
}
