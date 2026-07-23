<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAttachment extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id', 'lead_id', 'uploaded_by_user_id',
        'original_name', 'storage_path', 'mime_type', 'size_bytes',
    ];

    protected $casts = ['size_bytes' => 'integer'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
