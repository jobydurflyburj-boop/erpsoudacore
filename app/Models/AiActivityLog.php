<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiActivityLog extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'feature', 'provider', 'model', 'summary', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
