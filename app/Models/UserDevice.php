<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id', 'user_id', 'device_fingerprint', 'device_name',
        'platform', 'last_ip_address', 'first_seen_at', 'last_seen_at', 'is_trusted',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_trusted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
