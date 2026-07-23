<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    public function createCustomRole(Tenant $tenant, string $code, string $nameEn, ?string $nameAr, array $permissionNames): Role
    {
        if ($this->roles->findByCode($code, $tenant->id)) {
            throw new InvalidArgumentException("A role with code '{$code}' already exists for this company.");
        }

        $role = $this->roles->create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'is_system_role' => false,
        ]);

        $this->assignPermissions($role, $permissionNames);

        return $role;
    }

    public function updateRole(Role $role, array $attributes): Role
    {
        if ($role->is_system_role && array_key_exists('code', $attributes)) {
            throw new RuntimeException('The code of a default system role cannot be changed.');
        }

        return $this->roles->update($role, $attributes);
    }

    public function assignPermissions(Role $role, array $permissionNames): Role
    {
        $permissions = $this->permissions->findManyByNames($permissionNames);

        if ($permissions->count() !== count($permissionNames)) {
            throw new InvalidArgumentException('One or more permission names are invalid.');
        }

        $this->roles->syncPermissions($role, $permissions->pluck('id')->all());

        return $role->fresh('permissions');
    }

    public function deleteRole(Role $role): void
    {
        if ($role->is_system_role) {
            throw new RuntimeException('Default system roles cannot be deleted — edit their permissions instead, or duplicate as a custom role.');
        }

        if ($role->users()->exists()) {
            throw new RuntimeException('This role is still assigned to one or more users — reassign them before deleting the role.');
        }

        $this->roles->delete($role);
    }
}
