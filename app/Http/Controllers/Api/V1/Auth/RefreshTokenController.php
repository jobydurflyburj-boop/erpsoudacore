<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\UserResource;
use App\Services\TokenService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RefreshTokenController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function __invoke(RefreshTokenRequest $request)
    {
        try {
            $result = $this->tokens->refresh($request->string('refresh_token'), $request);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['refresh_token' => $e->getMessage()]);
        }

        return $this->ok([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'expires_in' => $result['expires_in'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
