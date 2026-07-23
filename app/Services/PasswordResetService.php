<?php

namespace App\Services;

use App\Models\PasswordResetToken;
use App\Models\Tenant;
use App\Notifications\PasswordResetNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Laravel's built-in Password broker assumes a globally-unique `email`
 * column and a `password_reset_tokens` table keyed by email alone —
 * neither holds here (email is unique per-tenant). This is a small,
 * deliberate reimplementation of the same secure pattern (random token,
 * hashed at rest, time-limited, single-use) scoped by tenant.
 */
class PasswordResetService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    public function sendResetLink(Tenant $tenant, string $email): void
    {
        $user = $this->users->findByEmailForTenant($email, $tenant->id);

        // Deliberately do not reveal whether the email exists — the
        // caller (controller) always returns the same generic response
        // either way, to avoid account enumeration.
        if (! $user) {
            return;
        }

        $token = Str::random(64);

        PasswordResetToken::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $user->notify(new PasswordResetNotification($token, $tenant->subdomain));
    }

    public function reset(Tenant $tenant, string $email, string $token, string $newPassword): void
    {
        $record = PasswordResetToken::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            throw new RuntimeException('This password reset link is invalid.');
        }

        if ($record->created_at->addMinutes(60)->isPast()) {
            throw new RuntimeException('This password reset link has expired.');
        }

        $user = $this->users->findByEmailForTenant($email, $tenant->id);

        if (! $user) {
            throw new RuntimeException('This password reset link is invalid.');
        }

        $this->passwordPolicy->assertNotReused($user, $newPassword);

        $hashed = Hash::make($newPassword);

        $user->forceFill([
            'password' => $hashed,
            'password_changed_at' => now(),
        ])->save();

        $this->passwordPolicy->recordHistory($user, $hashed);

        $record->delete();

        // A password reset is a full trust boundary crossing — every
        // other session for this user is revoked, not just refreshed.
        app(TokenService::class)->revokeAllForUser($user);
    }
}
