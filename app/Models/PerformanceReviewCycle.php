<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReviewCycle extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = ['tenant_id', 'name', 'period_start', 'period_end', 'status'];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function reviews(): HasMany { return $this->hasMany(PerformanceReview::class, 'cycle_id'); }
}
