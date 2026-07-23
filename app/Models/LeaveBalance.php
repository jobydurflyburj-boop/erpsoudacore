<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'employee_id', 'leave_type_id', 'year', 'allocated_days', 'used_days'];
    protected $casts = ['allocated_days' => 'decimal:2', 'used_days' => 'decimal:2'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }

    public function remainingDays(): float { return (float) $this->allocated_days - (float) $this->used_days; }
}
