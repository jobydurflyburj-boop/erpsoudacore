<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name', 'date', 'is_recurring_annually'];
    protected $casts = ['date' => 'date', 'is_recurring_annually' => 'boolean'];

    public function auditModule(): string { return 'hr_payroll'; }
}
