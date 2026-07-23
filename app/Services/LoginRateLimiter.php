<?php

namespace App\Services;

use App\Models\FailedLoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Two independent throttles, both must pass: per (tenant+email) so one
 * targeted account gets locked without needing to touch every other
 * tenant's users, and per IP so a credential-stuffing run against many
 * emails from one source is still caught even if each individual email
 * only sees one or two attempts.
 */
class LoginRateLimiter
{
    public function tooManyAttempts(string $tenantId, string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->emailKey($tenantId, $email), $this->maxAttempts())
            || RateLimiter::tooManyAttempts($this->ipKey($ip), $this->maxAttempts() * 4);
    }

    public function availableInSeconds(string $tenantId, string $email, string $ip): int
    {
        return max(
            RateLimiter::availableIn($this->emailKey($tenantId, $email)),
            RateLimiter::availableIn($this->ipKey($ip))
        );
    }

    public function recordFailure(?string $tenantId, string $email, Request $request, string $reason): void
    {
        RateLimiter::hit($this->emailKey($tenantId ?? 'unknown', $email), $this->lockoutSeconds());
        RateLimiter::hit($this->ipKey($request->ip()), $this->lockoutSeconds());

        FailedLoginAttempt::create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function clear(string $tenantId, string $email, string $ip): void
    {
        RateLimiter::clear($this->emailKey($tenantId, $email));
        RateLimiter::clear($this->ipKey($ip));
    }

    private function emailKey(string $tenantId, string $email): string
    {
        return 'login-attempts:'.$tenantId.':'.mb_strtolower($email);
    }

    private function ipKey(string $ip): string
    {
        return 'login-attempts-ip:'.$ip;
    }

    private function maxAttempts(): int
    {
        return (int) config('security.failed_login.max_attempts');
    }

    private function lockoutSeconds(): int
    {
        return (int) config('security.failed_login.lockout_minutes') * 60;
    }
}
