<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'event', 'module', 'description',
        'ip_address', 'user_agent', 'browser', 'context', 'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];
}
