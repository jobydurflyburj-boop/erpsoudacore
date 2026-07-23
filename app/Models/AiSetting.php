<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id', 'is_enabled', 'provider_override', 'insights_enabled',
        'notifications_enabled', 'automation_suggestions_enabled',
    ];
    protected $casts = [
        'is_enabled' => 'boolean', 'insights_enabled' => 'boolean',
        'notifications_enabled' => 'boolean', 'automation_suggestions_enabled' => 'boolean',
    ];

    public function auditModule(): string { return 'ai'; }
}
