<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBillItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'supplier_bill_id', 'product_id', 'description', 'quantity', 'unit_cost', 'vat_rate', 'line_total'];
    protected $casts = ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'vat_rate' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function supplierBill(): BelongsTo { return $this->belongsTo(SupplierBill::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
