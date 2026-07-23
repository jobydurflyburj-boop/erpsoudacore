<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Multitenancy\TenantContext;
use App\Services\PasswordResetService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(ResetPasswordRequest $request)
    {
        if (! $this->tenantContext->hasTenant()) {
            throw ValidationException::withMessages(['email' => 'No company could be resolved for this request.']);
        }

        try {
            $this->passwordReset->reset(
                $this->tenantContext->tenant(),
                $request->string('email'),
                $request->string('token'),
                $request->string('password')
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['token' => $e->getMessage()]);
        }

        return $this->ok(['message' => 'Password has been reset. Please log in with your new password.']);
    }
}
