<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'goods_receipt_id', 'product_id', 'quantity', 'unit_cost'];
    protected $casts = ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2'];

    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
