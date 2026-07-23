<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use BelongsToTenant, HasUuid;

    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'conversation_id', 'role', 'content', 'provider', 'model', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(AiConversation::class, 'conversation_id'); }
}
