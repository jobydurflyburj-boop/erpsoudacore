<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityStage extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name_en', 'name_ar', 'color', 'default_probability',
        'is_won', 'is_lost', 'is_default', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_probability' => 'integer',
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

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }

    public function isClosed(): bool
    {
        return $this->is_won || $this->is_lost;
    }
}
