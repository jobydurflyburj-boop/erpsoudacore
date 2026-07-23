<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Super Admin Console business logic for acting on a tenant. Deliberately
 * separate from TenantRepository (plain data access) — suspension is a
 * state transition with side effects (revoke every session, log it both
 * on the platform's own audit trail AND inside the affected tenant's own
 * activity log so their Company Owner can see why they were locked out),
 * not just an UPDATE statement.
 */
class SuperAdminTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly ActivityLogService $activityLog,
        private readonly AuditLogService $auditLog,
    ) {}

    public function suspend(User $actor, Tenant $tenant, string $reason): Tenant
    {
        if ($tenant->status === 'suspended') {
            throw new InvalidArgumentException('This tenant is already suspended.');
        }

        return DB::transaction(function () use ($actor, $tenant, $reason) {
            $tenant = $this->tenants->update($tenant, [
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'suspended_by_user_id' => $actor->id,
            ]);

            // Every active session for every user in this tenant is
            // revoked immediately — a suspended tenant's users should not
            // remain logged in just because their access token hasn't
            // expired yet.
            $tokenService = app(TokenService::class);
            User::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->get()->each(
                fn (User $user) => $tokenService->revokeAllForUser($user)
            );

            $this->logBothSides($actor, $tenant, 'tenant.suspended', "Tenant suspended: {$reason}");

            return $tenant;
        });
    }

    public function reactivate(User $actor, Tenant $tenant): Tenant
    {
        if ($tenant->status !== 'suspended') {
            throw new InvalidArgumentException('Only a suspended tenant can be reactivated.');
        }

        $tenant = $this->tenants->update($tenant, [
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
            'suspended_by_user_id' => null,
        ]);

        $this->logBothSides($actor, $tenant, 'tenant.reactivated', 'Tenant reactivated by platform administrator.');

        return $tenant;
    }

    /**
     * Writes to BOTH the platform's own audit trail (tenant_id = null,
     * visible only to Super Admin sessions under RLS) AND the affected
     * tenant's own activity_logs (tenant_id = that tenant, visible to
     * their own Company Owner) — a tenant should be able to see in their
     * own activity feed that they were suspended and why, not just have
     * it happen invisibly from their side.
     */
    private function logBothSides(User $actor, Tenant $tenant, string $event, string $description): void
    {
        $this->activityLog->record($actor, $tenant->id, $event, $description, null, [
            'tenant_id' => $tenant->id, 'actor_id' => $actor->id,
        ], 'platform');

        $this->auditLog->log('updated', $tenant, null, ['status' => $tenant->status]);
    }
}
