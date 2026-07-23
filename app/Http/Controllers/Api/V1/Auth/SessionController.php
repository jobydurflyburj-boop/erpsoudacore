<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionResource;
use App\Models\RefreshToken;
use App\Services\TokenService;
use Illuminate\Http\Request;

/**
 * "Multi Device Sessions" management — lists every live refresh-token
 * session (one per device, per TokenService) and lets the user revoke
 * any of them individually, e.g. "sign out my old phone".
 */
class SessionController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function index(Request $request)
    {
        $sessions = RefreshToken::where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return SessionResource::collection($sessions);
    }

    public function destroy(Request $request, string $id)
    {
        $token = RefreshToken::where('user_id', $request->user()->id)->findOrFail($id);

        $this->tokens->revoke($token);

        return response()->json(null, 204);
    }
}
