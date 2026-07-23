<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryComponent extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'employee_id', 'salary_component_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function salaryComponent(): BelongsTo { return $this->belongsTo(SalaryComponent::class); }
}
