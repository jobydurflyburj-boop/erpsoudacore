<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Multitenancy\TenantContext;
use App\Services\AuthService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(LoginRequest $request)
    {
        if (! $this->tenantContext->hasTenant()) {
            throw ValidationException::withMessages(['email' => 'No company could be resolved for this request.']);
        }

        try {
            $result = $this->auth->attemptLogin(
                $this->tenantContext->tenant(),
                $request->string('email'),
                $request->string('password'),
                $request,
                $request->boolean('remember_me')
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        if ($result['status'] === 'otp_required') {
            return $this->ok(['status' => 'otp_required', 'ticket' => $result['ticket']]);
        }

        return $this->ok([
            'status' => 'authenticated',
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'expires_in' => $result['expires_in'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
