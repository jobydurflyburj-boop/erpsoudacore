<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'password_hash', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
