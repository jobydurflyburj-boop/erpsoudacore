<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'inventory'; }

    public function products(): HasMany { return $this->hasMany(Product::class, 'brand_id'); }
}
