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

class SupplierBill extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUSES = ['draft', 'approved', 'paid', 'partial', 'overdue', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'document_number', 'supplier_id', 'purchase_order_id', 'goods_receipt_id',
        'status', 'document_date', 'due_date', 'subtotal', 'vat_amount', 'total',
        'paid_amount', 'credited_amount', 'notes', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'document_date' => 'date', 'due_date' => 'date',
        'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
        'paid_amount' => 'decimal:2', 'credited_amount' => 'decimal:2',
    ];

    public function auditModule(): string { return 'purchase'; }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function items(): HasMany { return $this->hasMany(SupplierBillItem::class); }
    public function allocations(): HasMany { return $this->hasMany(SupplierPaymentAllocation::class, 'supplier_bill_id'); }
    public function debitNotes(): HasMany { return $this->hasMany(DebitNote::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }

    public function balanceDue(): float
    {
        return round(((float) $this->total) - ((float) $this->paid_amount) - ((float) $this->credited_amount), 2);
    }
}
