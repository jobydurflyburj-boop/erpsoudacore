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

class PurchaseReturn extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'document_number', 'supplier_id', 'goods_receipt_id', 'warehouse_id', 'debit_note_id',
        'status', 'document_date', 'reason', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = ['document_date' => 'date'];

    public function auditModule(): string { return 'purchase'; }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function debitNote(): BelongsTo { return $this->belongsTo(DebitNote::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseReturnItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
