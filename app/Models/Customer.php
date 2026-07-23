<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const TYPE_COMPANY = 'company';
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPES = [self::TYPE_COMPANY, self::TYPE_INDIVIDUAL];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /** Same record-level scoping rule as Lead — a Sales role's own book of customers, not the whole company's. */
    public const OWN_RECORDS_ONLY_ROLES = [Role::SALES];

    protected $fillable = [
        'tenant_id', 'customer_number', 'customer_type', 'company_name',
        'first_name', 'last_name', 'arabic_name', 'email', 'phone', 'whatsapp',
        'country', 'city', 'address', 'vat_number', 'account_manager_user_id',
        'status', 'credit_limit', 'payment_terms_days', 'notes',
        'source_lead_id', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    public function auditModule(): string
    {
        return 'crm';
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_user_id');
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class)->latest('created_at');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
