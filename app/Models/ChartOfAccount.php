<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOfAccount extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    protected $fillable = ['tenant_id', 'code', 'name_en', 'name_ar', 'type', 'parent_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'accounting'; }

    public function parent(): BelongsTo { return $this->belongsTo(ChartOfAccount::class, 'parent_id'); }
}
