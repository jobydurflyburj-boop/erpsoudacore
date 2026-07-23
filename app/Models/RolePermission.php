<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Wired via Role::permissions()->using(RolePermission::class) — extending
 * Pivot (not a plain Model) keeps standard belongsToMany attach/detach/
 * sync behavior working normally while still giving this pivot a real
 * UUID primary key (HasUuid) instead of a composite one, and a place to
 * hang tenant scoping if a query ever needs to go through this model
 * directly rather than through the relation.
 *
 * tenant_id is NOT auto-filled from TenantContext here — see
 * RoleRepository::syncPermissions and RoleProvisioningService, both of
 * which pass it explicitly via $role->tenant_id, since role-provisioning
 * can run in contexts (registration, seeding) where TenantContext isn't
 * bound to the role's own tenant. Relying on implicit auto-fill here
 * previously caused every synced permission to be written with a NULL
 * tenant_id — invisible to every tenant session under RLS.
 */
class RolePermission extends Pivot
{
    use HasUuid;

    protected $table = 'role_permissions';

    public $incrementing = false;

    protected $fillable = ['tenant_id', 'role_id', 'permission_id'];
}
