<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentAllocation extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'supplier_payment_id', 'supplier_bill_id', 'amount', 'created_at'];
    protected $casts = ['amount' => 'decimal:2', 'created_at' => 'datetime'];

    public function payment(): BelongsTo { return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id'); }
    public function bill(): BelongsTo { return $this->belongsTo(SupplierBill::class, 'supplier_bill_id'); }
}
