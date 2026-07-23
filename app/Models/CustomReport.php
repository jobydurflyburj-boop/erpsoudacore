<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReport extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name', 'description', 'source', 'columns', 'filters', 'group_by', 'created_by_user_id'];
    protected $casts = ['columns' => 'array', 'filters' => 'array'];

    public function auditModule(): string { return 'reports'; }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
