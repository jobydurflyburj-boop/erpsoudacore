<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * A prospective customer's signup, before Super Admin approval. Deliberately
 * NOT tenant-scoped (BelongsToTenant) — this table lives entirely at the
 * platform level, same as Tenant itself; no tenant exists yet for a row
 * here until it's approved.
 */
class TenantRegistrationRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'legal_name', 'subdomain', 'trade_name', 'cr_number', 'vat_number',
        'admin_full_name', 'admin_email', 'admin_password_hash',
        'default_locale', 'status', 'tenant_id', 'reviewed_by',
        'reviewed_at', 'rejection_reason',
    ];

    protected $hidden = ['admin_password_hash'];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
