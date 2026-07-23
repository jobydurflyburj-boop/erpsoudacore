<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use BelongsToTenant, HasUuid;

    public const TYPE_CREATED = 'created';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_NOTE = 'note';
    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_ATTACHMENT_ADDED = 'attachment_added';
    public const TYPE_CONVERTED = 'converted';

    public const MANUAL_TYPES = [self::TYPE_NOTE, self::TYPE_CALL, self::TYPE_EMAIL, self::TYPE_WHATSAPP];

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'lead_id', 'user_id', 'type', 'description', 'metadata', 'created_at'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
