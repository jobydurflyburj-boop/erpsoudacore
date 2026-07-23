<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Looks up a user by email WITHIN a specific tenant — never globally,
     * since email is only unique per-tenant. $tenantId is explicit here
     * rather than relying on the BelongsToTenant global scope, because
     * this is called during login BEFORE TenantContext may be fully
     * trusted (see AuthService::attemptLogin).
     */
    public function findByEmailForTenant(string $email, string $tenantId): ?User;

    public function recentPasswordHashes(User $user, int $limit): Collection;

    /** Platform-level lookup (tenant_id IS NULL) — Super Admin accounts only. */
    public function findSuperAdminByEmail(string $email): ?User;
}
