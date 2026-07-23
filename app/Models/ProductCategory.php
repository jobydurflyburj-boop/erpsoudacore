<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'name_en', 'name_ar', 'parent_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'inventory'; }

    public function parent(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'parent_id'); }
    public function products(): HasMany { return $this->hasMany(Product::class, 'category_id'); }
}
