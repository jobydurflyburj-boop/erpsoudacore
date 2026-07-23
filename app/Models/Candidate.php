<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'full_name', 'email', 'phone', 'resume_notes'];

    public function auditModule(): string { return 'hr_payroll'; }

    public function applications(): HasMany { return $this->hasMany(JobApplication::class); }
}
