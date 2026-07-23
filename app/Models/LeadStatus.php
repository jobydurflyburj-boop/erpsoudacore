<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStatus extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name_en', 'name_ar', 'color',
        'is_won', 'is_lost', 'is_default', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function auditModule(): string
    {
        return 'crm';
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isClosed(): bool
    {
        return $this->is_won || $this->is_lost;
    }
}
