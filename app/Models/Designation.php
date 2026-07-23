<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'title_en', 'title_ar', 'department_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
}
