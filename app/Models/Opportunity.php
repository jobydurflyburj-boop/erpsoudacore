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

class Opportunity extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];

    /** Same record-level scoping rule as Lead/Customer. */
    public const OWN_RECORDS_ONLY_ROLES = [Role::SALES];

    protected $fillable = [
        'tenant_id', 'opportunity_number', 'name', 'customer_id', 'lead_id',
        'stage_id', 'amount', 'probability', 'expected_close_date', 'closed_at',
        'assigned_to_user_id', 'priority', 'notes',
        'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function auditModule(): string
    {
        return 'crm';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class, 'stage_id');
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
        return $this->hasMany(OpportunityActivity::class)->latest('created_at');
    }

    public function weightedValue(): ?float
    {
        return $this->amount !== null
            ? round(((float) $this->amount) * $this->probability / 100, 2)
            : null;
    }
}
