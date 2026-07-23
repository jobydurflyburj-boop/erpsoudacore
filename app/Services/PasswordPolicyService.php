<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

/**
 * Centralizes password strength + reuse rules so every entry point
 * (registration, reset, change-password, admin-created-user) enforces
 * the identical policy — never duplicated per-controller.
 */
class PasswordPolicyService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function rule(): Password
    {
        // OWASP-aligned: length over composition rules (composition
        // requirements push users toward predictable substitutions like
        // "P@ssw0rd1" rather than genuine entropy).
        return Password::min(10)
            ->max(72) // bcrypt/argon2 input practicalities
            ->uncompromised(); // checks against known-breached password corpuses (Laravel's HIBP k-anonymity integration)
    }

    public function assertNotReused(User $user, string $newPassword): void
    {
        $limit = (int) config('security.password_history_limit', 5);

        $recent = $this->users->recentPasswordHashes($user, $limit);

        foreach ($recent as $hash) {
            if (Hash::check($newPassword, $hash)) {
                throw new InvalidArgumentException(
                    "This password was used recently. Choose one of your last {$limit} passwords differently."
                );
            }
        }
    }

    public function recordHistory(User $user, string $hashedPassword): void
    {
        $user->passwordHistories()->create([
            'tenant_id' => $user->tenant_id,
            'password_hash' => $hashedPassword,
            'created_at' => now(),
        ]);

        // Trim to the configured limit so the table doesn't grow unbounded per user.
        $limit = (int) config('security.password_history_limit', 5);

        $ids = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->pluck('id')
            ->slice($limit)
            ->values();

        if ($ids->isNotEmpty()) {
            $user->passwordHistories()->whereIn('id', $ids)->delete();
        }
    }
}
