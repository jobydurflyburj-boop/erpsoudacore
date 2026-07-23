<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_APPLIED = 'applied';
    public const STATUS_SCREENING = 'screening';
    public const STATUS_INTERVIEW = 'interview';
    public const STATUS_OFFERED = 'offered';
    public const STATUS_HIRED = 'hired';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['tenant_id', 'job_opening_id', 'candidate_id', 'status', 'applied_at', 'notes'];
    protected $casts = ['applied_at' => 'date'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function jobOpening(): BelongsTo { return $this->belongsTo(JobOpening::class); }
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
