<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LogoutController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(Request $request)
    {
        $request->validate(['refresh_token' => ['required', 'string']]);

        $hash = hash('sha256', $request->string('refresh_token'));
        $token = RefreshToken::where('token_hash', $hash)->where('user_id', $request->user()->id)->first();

        if (! $token) {
            throw ValidationException::withMessages(['refresh_token' => 'Invalid session.']);
        }

        $this->auth->logout($token, $request->user(), $request);

        return response()->json(null, 204);
    }
}
