<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = ['tenant_id', 'supplier_number', 'name', 'email', 'phone', 'vat_number', 'payment_terms_days', 'is_active'];
    protected $casts = ['payment_terms_days' => 'integer', 'is_active' => 'boolean'];

    public function auditModule(): string { return 'purchase'; }

    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}
