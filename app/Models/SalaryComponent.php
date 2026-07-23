<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const TYPE_ALLOWANCE = 'allowance';
    public const TYPE_DEDUCTION = 'deduction';
    public const CALC_FIXED = 'fixed';
    public const CALC_PERCENTAGE_OF_BASIC = 'percentage_of_basic';

    protected $fillable = [
        'tenant_id', 'name_en', 'name_ar', 'type', 'calculation_type',
        'default_amount', 'is_taxable', 'is_active',
    ];

    protected $casts = ['default_amount' => 'decimal:2', 'is_taxable' => 'boolean', 'is_active' => 'boolean'];

    public function auditModule(): string { return 'hr_payroll'; }
}
