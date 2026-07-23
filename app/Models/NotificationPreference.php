<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use BelongsToTenant, HasUuid;

    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PUSH = 'push';

    public const CHANNELS = [
        self::CHANNEL_IN_APP, self::CHANNEL_EMAIL, self::CHANNEL_SMS,
        self::CHANNEL_WHATSAPP, self::CHANNEL_PUSH,
    ];

    protected $fillable = ['tenant_id', 'user_id', 'category', 'channel', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
