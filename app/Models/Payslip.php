<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use BelongsToTenant, HasUuid;

    public const STATUS_GENERATED = 'generated';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'tenant_id', 'payroll_run_id', 'employee_id', 'basic_salary', 'total_allowances',
        'overtime_amount', 'total_deductions', 'gross_pay', 'net_pay', 'status', 'paid_at',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2', 'total_allowances' => 'decimal:2', 'overtime_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2', 'gross_pay' => 'decimal:2', 'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo { return $this->belongsTo(PayrollRun::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function lines(): HasMany { return $this->hasMany(PayslipLine::class); }
}
