<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    /** Roles required to complete an MFA/OTP step in addition to password — see docs/FOUNDATION.md "Authentication". Public: also the single source of truth for User::mfaEnabled(). */
    public const MFA_REQUIRED_ROLES = [Role::COMPANY_OWNER, Role::ADMIN, Role::ACCOUNTANT, Role::HR];

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly DeviceService $devices,
        private readonly OtpService $otp,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @return array{status: 'authenticated'|'otp_required', ...}
     * @throws RuntimeException on invalid credentials, locked account, inactive tenant/user
     */
    public function attemptLogin(Tenant $tenant, string $email, string $password, Request $request, bool $rememberMe = false): array
    {
        if (! $tenant->isActive()) {
            throw new RuntimeException('This company account is currently '.$tenant->status.'. Contact support to resolve billing or account status.');
        }

        if ($this->rateLimiter->tooManyAttempts($tenant->id, $email, $request->ip())) {
            $seconds = $this->rateLimiter->availableInSeconds($tenant->id, $email, $request->ip());
            throw new RuntimeException("Too many failed attempts. Try again in {$seconds} seconds.");
        }

        $user = $this->users->findByEmailForTenant($email, $tenant->id);

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->rateLimiter->recordFailure($tenant->id, $email, $request, 'invalid_credentials');
            $this->activityLog->record($user, $tenant->id, 'auth.login_failed', 'Invalid credentials.', $request);

            throw new RuntimeException('These credentials do not match our records.');
        }

        if (! $user->isActive()) {
            $this->rateLimiter->recordFailure($tenant->id, $email, $request, 'account_disabled');

            throw new RuntimeException('This account is disabled. Contact your company administrator.');
        }

        $this->rateLimiter->clear($tenant->id, $email, $request->ip());

        if ($this->requiresMfa($user)) {
            $ticket = $this->otp->generateWithTicket($user, \App\Models\OtpCode::PURPOSE_LOGIN_VERIFICATION, $user->email);

            return ['status' => 'otp_required', 'ticket' => $ticket];
        }

        return $this->completeLogin($user, $request, $rememberMe);
    }

    public function verifyLoginOtp(string $ticket, string $code, Request $request, bool $rememberMe = false): array
    {
        $user = $this->otp->verifyByTicket($ticket, $code); // throws RuntimeException on any failure

        return $this->completeLogin($user, $request, $rememberMe);
    }

    private function completeLogin(User $user, Request $request, bool $rememberMe): array
    {
        $this->devices->registerOrTouch($user, $request);

        $tokens = $this->tokens->issue($user, $request, $rememberMe);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->activityLog->record($user, $user->tenant_id, 'auth.login', 'Successful login.', $request);

        return array_merge($tokens, ['status' => 'authenticated', 'user' => $user->fresh(['role', 'company', 'defaultBranch'])]);
    }

    /**
     * Platform-level login — no tenant subdomain, tenant_id IS NULL rows
     * only. Deliberately sets is_super_admin on TenantContext BEFORE the
     * credential lookup: this is a fixed, backend-controlled query (not
     * built from user input) so bypassing RLS for exactly this one lookup
     * is safe, and it's the only way to find a Super Admin row at all
     * given RLS's default-deny posture. See docs/FOUNDATION.md
     * "Authentication — Super Admin login".
     */
    public function attemptSuperAdminLogin(string $email, string $password, Request $request): array
    {
        app(\App\Multitenancy\TenantContext::class)->setSuperAdmin(true);
        app(\App\Multitenancy\TenantContext::class)->apply();

        if ($this->rateLimiter->tooManyAttempts('platform', $email, $request->ip())) {
            $seconds = $this->rateLimiter->availableInSeconds('platform', $email, $request->ip());
            throw new RuntimeException("Too many failed attempts. Try again in {$seconds} seconds.");
        }

        $user = $this->users->findSuperAdminByEmail($email);

        if (! $user || ! Hash::check($password, $user->password) || $user->role?->code !== Role::SUPER_ADMIN) {
            $this->rateLimiter->recordFailure(null, $email, $request, 'invalid_credentials');

            throw new RuntimeException('These credentials do not match our records.');
        }

        if (! $user->isActive()) {
            throw new RuntimeException('This account is disabled.');
        }

        $this->rateLimiter->clear('platform', $email, $request->ip());

        return $this->completeLogin($user, $request, rememberMe: false);
    }

    public function logout(RefreshToken $refreshToken, User $user, Request $request): void
    {
        $this->tokens->revoke($refreshToken);
        $this->activityLog->record($user, $user->tenant_id, 'auth.logout', 'User logged out.', $request);
    }

    public function logoutAllDevices(User $user, Request $request): void
    {
        $this->tokens->revokeAllForUser($user);
        $this->activityLog->record($user, $user->tenant_id, 'auth.logout_all_devices', 'All device sessions revoked.', $request);
    }

    private function requiresMfa(User $user): bool
    {
        return $user->role && in_array($user->role->code, self::MFA_REQUIRED_ROLES, true);
    }
}
