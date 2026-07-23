<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const SUPER_ADMIN = 'super_admin';
    public const COMPANY_OWNER = 'company_owner';
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const SALES = 'sales';
    public const ACCOUNTANT = 'accountant';
    public const HR = 'hr';
    public const INVENTORY = 'inventory';
    public const CASHIER = 'cashier';
    public const EMPLOYEE = 'employee';

    protected $fillable = ['tenant_id', 'code', 'name_en', 'name_ar', 'is_system_role'];

    protected $casts = ['is_system_role' => 'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->using(RolePermission::class)
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $module, string $action): bool
    {
        return in_array(
            Permission::key($module, $action),
            $this->cachedPermissionKeys(),
            true
        );
    }

    /**
     * Cached for the lifetime of a request-adjacent TTL — permission
     * checks happen on every RBAC-guarded API call (CheckPermission
     * middleware), so this avoids a join query per request. Invalidated
     * explicitly by RoleService whenever a role's permissions change.
     */
    public function cachedPermissionKeys(): array
    {
        return Cache::remember(
            "role:{$this->id}:permission_keys",
            now()->addMinutes(30),
            fn () => $this->permissions()->pluck('name')->all()
        );
    }

    public function forgetPermissionCache(): void
    {
        Cache::forget("role:{$this->id}:permission_keys");
    }
}
