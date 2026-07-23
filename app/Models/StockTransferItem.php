<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'stock_transfer_id', 'product_id', 'quantity'];
    protected $casts = ['quantity' => 'decimal:3'];

    public function stockTransfer(): BelongsTo { return $this->belongsTo(StockTransfer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
