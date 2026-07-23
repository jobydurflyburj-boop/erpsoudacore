<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerActivity extends Model
{
    use BelongsToTenant, HasUuid;

    public const TYPE_CREATED = 'created';
    public const TYPE_CONVERTED_FROM_LEAD = 'converted_from_lead';
    public const TYPE_ACCOUNT_MANAGER_CHANGED = 'account_manager_changed';
    public const TYPE_NOTE = 'note';
    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_WHATSAPP = 'whatsapp';

    public const MANUAL_TYPES = [self::TYPE_NOTE, self::TYPE_CALL, self::TYPE_EMAIL, self::TYPE_WHATSAPP];

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'customer_id', 'user_id', 'type', 'description', 'metadata', 'created_at'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
