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

class SalesReturn extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'document_number', 'customer_id', 'sales_invoice_id', 'warehouse_id', 'credit_note_id',
        'status', 'document_date', 'reason', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = ['document_date' => 'date'];

    public function auditModule(): string { return 'sales'; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function salesInvoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
    public function items(): HasMany { return $this->hasMany(SalesReturnItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
