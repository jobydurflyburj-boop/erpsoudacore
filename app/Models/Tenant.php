<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'id', 'name', 'subdomain', 'status', 'subscription_plan_id',
        'default_locale', 'default_currency', 'timezone',
        'trial_ends_at', 'suspended_at', 'suspension_reason', 'suspended_by_user_id',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function suspendedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_user_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function isActive(): bool
    {
        return (bool) (config("tenancy.statuses.{$this->status}.active") ?? false);
    }

    public function defaultCompany(): ?Company
    {
        return $this->companies()->where('is_default', true)->first();
    }
}
