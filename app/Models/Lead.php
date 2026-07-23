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

class Lead extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];

    /** Role codes whose lead visibility/editing is scoped to their own assigned leads — see LeadPolicy. */
    public const OWN_RECORDS_ONLY_ROLES = [Role::SALES];

    protected $fillable = [
        'tenant_id', 'lead_number', 'company_name', 'first_name', 'last_name',
        'arabic_name', 'email', 'phone', 'whatsapp', 'country', 'city',
        'lead_source_id', 'lead_status_id', 'assigned_to_user_id',
        'expected_revenue', 'probability', 'priority', 'notes',
        'converted_to_customer_id', 'converted_at',
        'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'expected_revenue' => 'decimal:2',
        'probability' => 'integer',
        'converted_at' => 'datetime',
    ];

    public function auditModule(): string
    {
        return 'crm';
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
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
        return $this->hasMany(LeadActivity::class)->latest('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeadAttachment::class);
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function isConverted(): bool
    {
        return $this->converted_to_customer_id !== null;
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
