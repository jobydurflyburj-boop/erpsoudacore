<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    protected string $modelClass = Role::class;

    protected array $allowedFilters = ['is_system_role'];

    protected array $allowedSorts = ['created_at', 'name_en'];

    protected array $searchableFields = ['name_en', 'name_ar', 'code'];

    public function findByCode(string $code, ?string $tenantId): ?Role
    {
        return Role::withoutGlobalScope('tenant')
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function forTenant(string $tenantId): Collection
    {
        return Role::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        // Same fix as RoleProvisioningService::provisionDefaultRoles —
        // bare-ID sync() leaves role_permissions.tenant_id NULL, which
        // RLS then hides from every normal tenant session. $role->tenant_id
        // is used here (not TenantContext) deliberately: it's correct
        // regardless of whether this call happens inside a normal
        // authenticated request (TenantContext bound) or a service call
        // where it isn't (e.g. provisioning during registration).
        $role->permissions()->sync(
            collect($permissionIds)->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $role->tenant_id]])->all()
        );
        $role->forgetPermissionCache();
    }
}
