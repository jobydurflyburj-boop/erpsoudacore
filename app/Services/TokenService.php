<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owns the full lifecycle of "what proves this device is logged in":
 * Sanctum access tokens (short-lived, config/sanctum.php) + a
 * rotating opaque refresh token (this class). One (access, refresh) pair
 * per device session — Sanctum's personal_access_tokens table doubles as
 * the "multi-device sessions" list (SessionController reads it directly).
 *
 * Rotation-on-reuse: every refresh consumes the presented token and
 * issues a new one in the same "family". If a token that was already
 * consumed is presented again, that's a signal of token theft (someone
 * replayed an old refresh token) — the entire family is revoked and the
 * device must log in again.
 */
class TokenService
{
    public function issue(User $user, Request $request, bool $rememberMe = false, ?string $deviceName = null): array
    {
        $deviceName ??= $this->guessDeviceName($request);

        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration'))
        );

        $ttlDays = $rememberMe
            ? (int) config('security.tokens.remember_me_ttl_days')
            : (int) config('security.tokens.refresh_ttl_days');

        $plainRefreshToken = Str::random(80);

        $refreshToken = RefreshToken::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'personal_access_token_id' => $accessToken->accessToken->id,
            'token_hash' => hash('sha256', $plainRefreshToken),
            'family_id' => (string) Str::uuid(),
            'remember_me' => $rememberMe,
            'device_name' => $deviceName,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays($ttlDays),
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $plainRefreshToken,
            'expires_in' => (int) config('sanctum.expiration') * 60,
            'refresh_token_id' => $refreshToken->id,
        ];
    }

    /**
     * @throws RuntimeException when the token is invalid, expired, or a
     *         reused/revoked token is presented (theft signal).
     */
    public function refresh(string $plainRefreshToken, Request $request): array
    {
        $hash = hash('sha256', $plainRefreshToken);

        $token = RefreshToken::withoutTenantScope()
            ->where('token_hash', $hash)
            ->first();

        if (! $token) {
            throw new RuntimeException('Invalid refresh token.');
        }

        if ($token->revoked_at !== null) {
            // Reuse of an already-rotated-away token — revoke the whole
            // family and force re-authentication on every device in it.
            $this->revokeFamily($token->family_id);

            throw new RuntimeException('Refresh token has already been used. All sessions in this chain were revoked for safety — please log in again.');
        }

        if ($token->expires_at->isPast()) {
            throw new RuntimeException('Refresh token has expired.');
        }

        return DB::transaction(function () use ($token, $request) {
            $user = $token->user;

            // Revoke the presented token and its paired access token.
            $token->update(['revoked_at' => now()]);
            $user->tokens()->where('id', $token->personal_access_token_id)->delete();

            $accessToken = $user->createToken(
                $token->device_name ?? 'unknown-device',
                ['*'],
                now()->addMinutes((int) config('sanctum.expiration'))
            );

            $plainRefreshToken = Str::random(80);

            $newToken = RefreshToken::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'personal_access_token_id' => $accessToken->accessToken->id,
                'token_hash' => hash('sha256', $plainRefreshToken),
                'family_id' => $token->family_id, // same family — rotation, not a new session
                'remember_me' => $token->remember_me,
                'device_name' => $token->device_name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'expires_at' => $token->remember_me
                    ? now()->addDays((int) config('security.tokens.remember_me_ttl_days'))
                    : now()->addDays((int) config('security.tokens.refresh_ttl_days')),
            ]);

            return [
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $plainRefreshToken,
                'expires_in' => (int) config('sanctum.expiration') * 60,
                'user' => $user,
            ];
        });
    }

    public function revoke(RefreshToken $token): void
    {
        $token->update(['revoked_at' => now()]);
        $token->user->tokens()->where('id', $token->personal_access_token_id)->delete();
    }

    public function revokeAllForUser(User $user, ?string $exceptRefreshTokenId = null): void
    {
        DB::transaction(function () use ($user, $exceptRefreshTokenId) {
            $query = RefreshToken::where('user_id', $user->id)->whereNull('revoked_at');

            if ($exceptRefreshTokenId) {
                $query->where('id', '!=', $exceptRefreshTokenId);
            }

            $query->update(['revoked_at' => now()]);
            $user->tokens()->when(
                $exceptRefreshTokenId,
                fn ($q) => $q->whereNot('name', RefreshToken::find($exceptRefreshTokenId)?->device_name)
            )->delete();
        });
    }

    private function revokeFamily(string $familyId): void
    {
        $tokens = RefreshToken::withoutTenantScope()->where('family_id', $familyId)->get();

        foreach ($tokens as $token) {
            $token->update(['revoked_at' => now()]);
            $token->user?->tokens()->where('id', $token->personal_access_token_id)->delete();
        }
    }

    private function guessDeviceName(Request $request): string
    {
        $agent = (string) $request->userAgent();

        return Str::limit($agent ?: 'Unknown device', 60, '');
    }
}
