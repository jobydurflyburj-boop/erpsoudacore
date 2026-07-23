<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    protected $fillable = [
        'tenant_id', 'cycle_id', 'employee_id', 'reviewer_user_id', 'rating',
        'strengths', 'areas_for_improvement', 'status', 'submitted_at',
    ];
    protected $casts = ['rating' => 'integer', 'submitted_at' => 'datetime'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function cycle(): BelongsTo { return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_user_id'); }
}
