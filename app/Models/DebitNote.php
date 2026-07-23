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

class DebitNote extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';

    protected $fillable = [
        'tenant_id', 'document_number', 'supplier_id', 'supplier_bill_id', 'status',
        'document_date', 'subtotal', 'vat_amount', 'total', 'reason',
        'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'document_date' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function auditModule(): string { return 'purchase'; }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function supplierBill(): BelongsTo { return $this->belongsTo(SupplierBill::class); }
    public function items(): HasMany { return $this->hasMany(DebitNoteItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
