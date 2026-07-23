<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Platform-level login — deliberately a SEPARATE route/controller from
 * LoginController, not a branch inside it. See docs/FOUNDATION.md
 * "Authentication — Super Admin login" for why this separation is a
 * security requirement, not just code organization.
 */
class SuperAdminLoginController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(LoginRequest $request)
    {
        try {
            $result = $this->auth->attemptSuperAdminLogin(
                $request->string('email'),
                $request->string('password'),
                $request
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        return $this->ok([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'expires_in' => $result['expires_in'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
