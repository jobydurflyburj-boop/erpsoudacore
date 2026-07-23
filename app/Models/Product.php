<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'sku', 'barcode', 'name_en', 'name_ar', 'category', 'category_id', 'unit', 'unit_id', 'brand_id',
        'cost_price', 'sale_price', 'vat_rate', 'reorder_point', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'vat_rate' => 'decimal:2',
        'reorder_point' => 'decimal:3', 'is_active' => 'boolean',
    ];

    public function auditModule(): string { return 'inventory'; }

    public function stockLevels(): HasMany { return $this->hasMany(StockLevel::class); }
    public function categoryRef(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function unitRef(): BelongsTo { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }

    public function totalStock(): float
    {
        return (float) $this->stockLevels()->sum('quantity');
    }

    public function isLowStock(): bool
    {
        return (float) $this->reorder_point > 0 && $this->totalStock() <= (float) $this->reorder_point;
    }
}
