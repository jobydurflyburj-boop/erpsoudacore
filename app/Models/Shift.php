<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name', 'start_time', 'end_time', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'hr_payroll'; }
}
