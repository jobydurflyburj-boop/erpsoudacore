<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verification) {}

    public function verify(EmailVerificationRequest $request)
    {
        $verified = $this->verification->markVerified($request->user() ?? User::withoutGlobalScope('tenant')->findOrFail($request->route('id')));

        return $this->ok(['message' => $verified ? 'Email verified.' : 'Email was already verified.']);
    }

    public function resend(Request $request)
    {
        $this->verification->send($request->user());

        return $this->ok(['message' => 'Verification email sent.']);
    }
}
