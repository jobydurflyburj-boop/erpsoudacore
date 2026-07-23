<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToTenant, HasUuid;

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_ON_LEAVE = 'on_leave';

    protected $fillable = ['tenant_id', 'employee_id', 'date', 'check_in', 'check_out', 'status', 'shift_id', 'hours_worked'];
    protected $casts = ['date' => 'date', 'check_in' => 'datetime', 'check_out' => 'datetime', 'hours_worked' => 'decimal:2'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function shift(): BelongsTo { return $this->belongsTo(Shift::class); }
}
