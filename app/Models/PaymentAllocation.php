<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'customer_payment_id', 'sales_invoice_id', 'amount', 'created_at'];
    protected $casts = ['amount' => 'decimal:2', 'created_at' => 'datetime'];

    public function payment(): BelongsTo { return $this->belongsTo(CustomerPayment::class, 'customer_payment_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
}
