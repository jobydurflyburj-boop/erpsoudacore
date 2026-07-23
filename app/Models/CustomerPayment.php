<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid;

    public const METHODS = ['cash', 'bank_transfer', 'card', 'other'];

    protected $fillable = [
        'tenant_id', 'payment_number', 'customer_id', 'amount', 'allocated_amount',
        'payment_method', 'reference', 'payment_date', 'notes', 'created_by_user_id',
    ];

    protected $casts = ['amount' => 'decimal:2', 'allocated_amount' => 'decimal:2', 'payment_date' => 'date'];

    public function auditModule(): string { return 'sales'; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }

    public function unallocatedAmount(): float
    {
        return round(((float) $this->amount) - ((float) $this->allocated_amount), 2);
    }
}
