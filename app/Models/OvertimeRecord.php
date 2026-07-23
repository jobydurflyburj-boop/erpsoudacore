<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['tenant_id', 'employee_id', 'date', 'hours', 'rate_multiplier', 'amount', 'status', 'approved_by_user_id'];
    protected $casts = ['date' => 'date', 'hours' => 'decimal:2', 'rate_multiplier' => 'decimal:2', 'amount' => 'decimal:2'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_user_id'); }
}
