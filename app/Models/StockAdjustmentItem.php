<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'stock_adjustment_id', 'product_id', 'quantity_change', 'reason'];
    protected $casts = ['quantity_change' => 'decimal:3'];

    public function stockAdjustment(): BelongsTo { return $this->belongsTo(StockAdjustment::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
