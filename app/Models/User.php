<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Auditable, BelongsToTenant, HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INVITED = 'invited';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'tenant_id', 'company_id', 'default_branch_id', 'department_id', 'role_id',
        'email', 'full_name', 'avatar_path', 'phone', 'preferred_locale',
        'timezone', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches');
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function pushDeviceTokens()
    {
        return $this->hasMany(PushDeviceToken::class);
    }

    /**
     * MFA is mandatory-by-role (AuthService::MFA_REQUIRED_ROLES), not an
     * opt-in toggle — this reports the real, current state derived from
     * the user's role, rather than a separate column that could drift
     * out of sync with the actual enforcement logic.
     */
    public function mfaEnabled(): bool
    {
        return $this->role !== null && in_array(
            $this->role->code,
            \App\Services\AuthService::MFA_REQUIRED_ROLES,
            true
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function can($abilities, $arguments = []): bool
    {
        // Support Laravel's native can() for convenience in controllers,
        // backed by our RBAC tables rather than framework Gates/Policies
        // (which don't naturally know about per-tenant, DB-driven roles).
        if (is_string($abilities) && str_contains($abilities, '.')) {
            [$module, $action] = explode('.', $abilities, 2);

            return $this->role?->hasPermission($module, $action) ?? false;
        }

        return parent::can($abilities, $arguments);
    }
}
