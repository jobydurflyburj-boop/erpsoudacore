<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name_en', 'name_ar', 'days_per_year', 'is_paid', 'is_active'];
    protected $casts = ['is_paid' => 'boolean', 'is_active' => 'boolean'];

    public function auditModule(): string { return 'hr_payroll'; }
}
