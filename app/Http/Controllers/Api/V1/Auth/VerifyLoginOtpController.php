<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VerifyLoginOtpController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(VerifyOtpRequest $request)
    {
        // No user_id is ever accepted here — the ticket alone (opaque,
        // single-use, 5-minute TTL) identifies which login attempt this
        // is. See OtpService::verifyByTicket for why.
        try {
            $result = $this->auth->verifyLoginOtp(
                $request->string('ticket'),
                $request->string('code'),
                $request,
                $request->boolean('remember_me')
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
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
