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

class PurchaseOrder extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'po_number', 'supplier_id', 'status', 'order_date',
        'subtotal', 'vat_amount', 'total', 'notes', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'order_date' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function auditModule(): string { return 'purchase'; }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }
}
