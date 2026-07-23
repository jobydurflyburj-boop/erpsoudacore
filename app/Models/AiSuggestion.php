<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_OPEN = 'open';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED = 'actioned';

    protected $fillable = ['tenant_id', 'category', 'title', 'description', 'status', 'dismissed_at'];
    protected $casts = ['dismissed_at' => 'datetime'];

    public function auditModule(): string { return 'ai'; }
}
