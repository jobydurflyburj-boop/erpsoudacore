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

class SalesInvoice extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUSES = ['draft', 'issued', 'paid', 'partial', 'overdue', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'document_number', 'customer_id', 'sales_order_id', 'status',
        'document_date', 'due_date', 'subtotal', 'vat_amount', 'total', 'paid_amount', 'credited_amount',
        'notes', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'document_date' => 'date', 'due_date' => 'date',
        'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
        'paid_amount' => 'decimal:2', 'credited_amount' => 'decimal:2',
    ];

    public function auditModule(): string { return 'sales'; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function items(): HasMany { return $this->hasMany(SalesInvoiceItem::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class, 'sales_invoice_id'); }
    public function creditNotes(): HasMany { return $this->hasMany(CreditNote::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }

    /** Total obligation minus what's been paid AND minus what's been credited — a credit note is not a payment. */
    public function balanceDue(): float
    {
        return round(((float) $this->total) - ((float) $this->paid_amount) - ((float) $this->credited_amount), 2);
    }
}
