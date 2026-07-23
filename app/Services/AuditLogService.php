<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Backs the App\Models\Concerns\Auditable trait — every model that uses
 * that trait gets create/update/delete/restore rows here automatically.
 * Writes are append-only; see the audit_logs migration for the DB-level
 * note on why no UPDATE/DELETE grant exists on this table in production.
 */
class AuditLogService
{
    public function log(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        // tenant_id is intentionally allowed to be NULL here — a
        // platform-level row (e.g. the Super Admin role, tenant_id IS
        // NULL) is still a real change that should be audited, not
        // silently dropped. audit_logs.tenant_id is nullable for exactly
        // this reason; RLS then makes that row visible only to a
        // Super Admin session, which is the correct audience for it.
        //
        // We only skip entirely for models that don't carry a tenant_id
        // attribute AT ALL (e.g. Tenant itself isn't Auditable, so this
        // never actually fires for it today — this guard is defensive
        // for any future non-tenant-aware model that opts into the trait).
        if (! array_key_exists('tenant_id', $model->getAttributes()) && Auth::user() === null) {
            return;
        }

        $tenantId = array_key_exists('tenant_id', $model->getAttributes())
            ? $model->tenant_id
            : Auth::user()?->tenant_id;

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => RequestFacade::ip(),
            'created_at' => now(),
        ]);
    }
}
