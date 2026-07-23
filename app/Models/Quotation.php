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

class Quotation extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    protected $fillable = [
        'tenant_id', 'document_number', 'customer_id', 'opportunity_id', 'status', 'document_date',
        'subtotal', 'vat_amount', 'total', 'notes', 'created_by_user_id', 'updated_by_user_id',
    ];

    protected $casts = [
        'document_date' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function auditModule(): string { return 'sales'; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function opportunity(): BelongsTo { return $this->belongsTo(Opportunity::class); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class); }
    public function salesOrder(): HasMany { return $this->hasMany(SalesOrder::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }
}
