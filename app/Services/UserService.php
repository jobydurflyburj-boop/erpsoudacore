<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function invite(Tenant $tenant, array $data): User
    {
        if ($this->users->findByEmailForTenant($data['email'], $tenant->id)) {
            throw new InvalidArgumentException('A user with this email already exists in this company.');
        }

        // Invited users get a random, unusable-until-reset password —
        // they set their own via the password-reset flow triggered by
        // the invitation email, they never receive a generated password
        // in plaintext.
        $temporaryPassword = Str::random(40);

        $user = $this->users->create([
            'tenant_id' => $tenant->id,
            'company_id' => $data['company_id'] ?? $tenant->defaultCompany()?->id,
            'default_branch_id' => $data['default_branch_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'role_id' => $data['role_id'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'] ?? $tenant->default_locale,
            'timezone' => $data['timezone'] ?? $tenant->timezone,
            'status' => User::STATUS_INVITED,
        ]);

        if (! empty($data['branch_ids'])) {
            $this->syncBranchesWithTenant($user, $data['branch_ids']);
        }

        $user->notify(new VerifyEmailNotification($tenant->subdomain));
        app(PasswordResetService::class)->sendResetLink($tenant, $user->email);

        $this->activityLog->record(auth()->user(), $tenant->id, 'user.invited', "Invited {$user->email}.");

        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill(array_intersect_key($data, array_flip([
            'full_name', 'phone', 'preferred_locale', 'timezone',
        ])));
        $user->save();

        return $user;
    }

    public function changeRole(User $user, string $roleId): User
    {
        $user->update(['role_id' => $roleId]);
        $this->activityLog->record(auth()->user(), $user->tenant_id, 'user.role_changed', "Role changed for {$user->email}.");

        $user = $user->fresh('role');

        app(NotificationService::class)->send(
            $user,
            'role.changed',
            'Your role has been updated',
            "Your role is now {$user->role?->name_en}.",
            ['role_id' => $roleId]
        );

        return $user;
    }

    public function setStatus(User $user, string $status): User
    {
        if (! in_array($status, [User::STATUS_ACTIVE, User::STATUS_DISABLED], true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $user->update(['status' => $status]);

        if ($status === User::STATUS_DISABLED) {
            app(TokenService::class)->revokeAllForUser($user);
        }

        $this->activityLog->record(auth()->user(), $user->tenant_id, 'user.status_changed', "Status changed to {$status} for {$user->email}.");

        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        $this->passwordPolicy->assertNotReused($user, $newPassword);

        $hashed = Hash::make($newPassword);
        $user->forceFill(['password' => $hashed, 'password_changed_at' => now()])->save();
        $this->passwordPolicy->recordHistory($user, $hashed);

        app(TokenService::class)->revokeAllForUser($user);
        $this->activityLog->record($user, $user->tenant_id, 'auth.password_changed', 'Password changed by user.');
    }

    public function updateAvatar(User $user, string $storedPath): User
    {
        $user->update(['avatar_path' => $storedPath]);

        return $user;
    }

    /**
     * Admin-triggered reset — sends the exact same tenant-scoped reset
     * email a self-service "forgot password" would (PasswordResetService),
     * rather than generating and revealing a new password directly. The
     * admin never sees or sets the user's new password.
     */
    public function adminResetPassword(User $user): void
    {
        app(PasswordResetService::class)->sendResetLink($user->tenant, $user->email);
        $this->activityLog->record(auth()->user(), $user->tenant_id, 'user.password_reset_triggered', "Password reset triggered for {$user->email}.");
    }

    public function assignBranches(User $user, array $branchIds): User
    {
        $this->syncBranchesWithTenant($user, $branchIds);
        $this->activityLog->record(auth()->user(), $user->tenant_id, 'user.branches_assigned', "Branches updated for {$user->email}.");

        return $user->fresh('branches');
    }

    /**
     * $user->branches()->sync() with bare branch IDs writes user_branches.
     * tenant_id as NULL — RLS-protected and therefore invisible to every
     * normal tenant session (same bug class found and fixed for
     * role_permissions during the tenant isolation review: see
     * docs/TENANT_ISOLATION_REVIEW.md Finding 3). Explicit pivot data
     * closes it here too.
     */
    private function syncBranchesWithTenant(User $user, array $branchIds): void
    {
        $user->branches()->sync(
            collect($branchIds)->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $user->tenant_id]])->all()
        );
    }
}
