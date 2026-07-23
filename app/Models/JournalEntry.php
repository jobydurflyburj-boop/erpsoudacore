<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use Auditable, BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id', 'entry_number', 'entry_date', 'memo', 'source_type', 'source_id',
        'is_reversed', 'reversed_by_entry_id', 'created_by_user_id',
    ];
    protected $casts = ['entry_date' => 'date', 'is_reversed' => 'boolean'];

    public function auditModule(): string { return 'accounting'; }

    public function lines(): HasMany { return $this->hasMany(JournalEntryLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function reversedByEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id'); }

    public function totalDebit(): float { return (float) $this->lines()->sum('debit'); }
    public function totalCredit(): float { return (float) $this->lines()->sum('credit'); }
}
