<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Auditable, BelongsToTenant, HasUuid, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_RESIGNED = 'resigned';

    protected $fillable = [
        'tenant_id', 'employee_number', 'user_id', 'full_name', 'email', 'phone',
        'department_id', 'designation_id', 'shift_id', 'hire_date', 'termination_date',
        'employment_status', 'basic_salary', 'created_by_user_id',
    ];

    protected $casts = [
        'hire_date' => 'date', 'termination_date' => 'date', 'basic_salary' => 'decimal:2',
    ];

    public function auditModule(): string { return 'hr_payroll'; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function designation(): BelongsTo { return $this->belongsTo(Designation::class); }
    public function shift(): BelongsTo { return $this->belongsTo(Shift::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }

    public function salaryComponents(): HasMany { return $this->hasMany(EmployeeSalaryComponent::class); }
    public function leaveBalances(): HasMany { return $this->hasMany(LeaveBalance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function overtimeRecords(): HasMany { return $this->hasMany(OvertimeRecord::class); }
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function performanceReviews(): HasMany { return $this->hasMany(PerformanceReview::class); }
}
