<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    // Deliberately NOT tenant-scoped by the global scope trait — a failed
    // login before the tenant/user is even confirmed to exist must still
    // be recordable, and rate-limiting by IP alone (across tenants) is
    // part of how this table is used (see LoginRateLimiter).
    use HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'email', 'ip_address', 'user_agent', 'reason', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
