<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'payslip_id', 'salary_component_id', 'label', 'type', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function payslip(): BelongsTo { return $this->belongsTo(Payslip::class); }
    public function salaryComponent(): BelongsTo { return $this->belongsTo(SalaryComponent::class); }
}
