<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ON_HOLD = 'on_hold';

    protected $fillable = ['tenant_id', 'title', 'department_id', 'description', 'status', 'positions_count', 'posted_date', 'created_by_user_id'];
    protected $casts = ['posted_date' => 'date'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function applications(): HasMany { return $this->hasMany(JobApplication::class); }
}
