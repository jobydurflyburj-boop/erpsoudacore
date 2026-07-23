<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'purchase_return_id', 'product_id', 'quantity', 'unit_price', 'vat_rate'];
    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'vat_rate' => 'decimal:2'];

    public function purchaseReturn(): BelongsTo { return $this->belongsTo(PurchaseReturn::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
