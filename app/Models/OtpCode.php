<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use BelongsToTenant, HasUuid;

    public const PURPOSE_LOGIN_VERIFICATION = 'login_verification';
    public const PURPOSE_PHONE_VERIFICATION = 'phone_verification';
    public const PURPOSE_SENSITIVE_ACTION = 'sensitive_action';

    protected $fillable = [
        'tenant_id', 'user_id', 'purpose', 'code_hash', 'ticket_hash', 'destination',
        'attempts', 'expires_at', 'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }
}
