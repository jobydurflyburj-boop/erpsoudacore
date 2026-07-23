<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptTemplate extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    public const KEY_CHAT_SYSTEM = 'chat_system';
    public const KEY_DASHBOARD_INSIGHTS = 'dashboard_insights';
    public const KEY_SALES_INSIGHTS = 'sales_insights';
    public const KEY_INVENTORY_INSIGHTS = 'inventory_insights';
    public const KEY_FINANCIAL_INSIGHTS = 'financial_insights';
    public const KEY_CRM_INSIGHTS = 'crm_insights';

    protected $fillable = ['tenant_id', 'key', 'content', 'is_active', 'created_by_user_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function auditModule(): string { return 'ai'; }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
