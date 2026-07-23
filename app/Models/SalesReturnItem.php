<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'sales_return_id', 'product_id', 'quantity', 'unit_price', 'vat_rate'];
    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'vat_rate' => 'decimal:2'];

    public function salesReturn(): BelongsTo { return $this->belongsTo(SalesReturn::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
