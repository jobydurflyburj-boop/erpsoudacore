<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'tenant_id', 'run_number', 'period_month', 'period_year', 'status',
        'total_gross', 'total_deductions', 'total_net', 'processed_at', 'created_by_user_id',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2', 'total_deductions' => 'decimal:2', 'total_net' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function auditModule(): string { return 'hr_payroll'; }

    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
