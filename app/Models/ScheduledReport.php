<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    protected $fillable = [
        'tenant_id', 'name', 'report_key', 'custom_report_id', 'frequency', 'format',
        'recipients', 'is_active', 'next_run_at', 'last_run_at', 'created_by_user_id',
    ];
    protected $casts = [
        'recipients' => 'array', 'is_active' => 'boolean',
        'next_run_at' => 'datetime', 'last_run_at' => 'datetime',
    ];

    public function auditModule(): string { return 'reports'; }

    public function customReport(): BelongsTo { return $this->belongsTo(CustomReport::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
