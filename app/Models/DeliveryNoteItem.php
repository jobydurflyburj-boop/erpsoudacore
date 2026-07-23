<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'delivery_note_id', 'product_id', 'description', 'quantity'];
    protected $casts = ['quantity' => 'decimal:3'];

    public function deliveryNote(): BelongsTo { return $this->belongsTo(DeliveryNote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
