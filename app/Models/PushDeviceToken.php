<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PushDeviceToken extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'user_id', 'token', 'platform', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];
}
