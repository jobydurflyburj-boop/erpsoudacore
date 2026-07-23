<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, ?string $tenantId): ?Role;

    public function forTenant(string $tenantId): Collection;

    public function syncPermissions(Role $role, array $permissionIds): void;
}
