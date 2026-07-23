<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Multitenancy\TenantContext;
use App\Services\PasswordResetService;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(ForgotPasswordRequest $request)
    {
        if ($this->tenantContext->hasTenant()) {
            $this->passwordReset->sendResetLink($this->tenantContext->tenant(), $request->string('email'));
        }

        // Always the same response, whether or not the email exists —
        // prevents account enumeration via response-timing/content.
        return $this->ok(['message' => 'If an account exists for this email, a reset link has been sent.']);
    }
}
