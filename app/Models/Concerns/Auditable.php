<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogService;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;

/**
 * Opt-in per model (not global) — attach to any model whose changes need
 * a compliance-grade trail (users, roles, role_permissions, companies,
 * branches, departments, company_settings today; every future
 * business-data module's models tomorrow). Deliberately excludes
 * high-volume/low-value models (e.g. activity_logs/audit_logs themselves,
 * notifications) from ever being audited, to avoid an infinite
 * audit-the-audit loop.
 *
 * Writes to BOTH audit_logs (field-level diff, compliance record — see
 * AuditLogService) AND activity_logs (human-readable feed with module
 * attribution — the Platform Administration "Activity Log" screen reads
 * this one). One model event, two purposes, two tables — not a
 * duplicate write of the same data, since audit_logs carries the diff
 * and activity_logs carries a one-line description.
 */
trait Auditable
{
    /** Never written to audit_logs' diff or described in activity_logs — credential material has no place in an audit trail, hashed or not. */
    private static array $auditExcludedFields = ['password', 'remember_token'];

    /** Changes to ONLY these fields are still recorded in audit_logs (compliance diff) but skipped from the human-readable activity feed — already covered by an explicit, more meaningful event (e.g. AuthService's 'auth.login') elsewhere. Prevents every login from also spamming "updated User (last_login_at, last_login_ip)". */
    private static array $silentActivityFields = ['last_login_at', 'last_login_ip', 'password_changed_at'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $attributes = array_diff_key($model->getAttributes(), array_flip(self::$auditExcludedFields));
            app(AuditLogService::class)->log('created', $model, null, $attributes);
            static::recordActivity($model, 'created', "created a new ".static::humanName());
        });

        static::updated(function (Model $model) {
            $changes = array_diff_key($model->getChanges(), array_flip(array_merge(self::$auditExcludedFields, ['updated_at'])));

            if (empty($changes)) {
                return;
            }

            app(AuditLogService::class)->log(
                'updated',
                $model,
                array_intersect_key($model->getOriginal(), $changes),
                $changes
            );

            if (array_diff(array_keys($changes), self::$silentActivityFields) === []) {
                return; // only silent fields changed — skip the activity feed entry
            }

            static::recordActivity($model, 'updated', "updated ".static::humanName().' ('.implode(', ', array_keys($changes)).')');
        });

        static::deleted(function (Model $model) {
            $attributes = array_diff_key($model->getOriginal(), array_flip(self::$auditExcludedFields));
            app(AuditLogService::class)->log('deleted', $model, $attributes, null);
            static::recordActivity($model, 'deleted', "deleted a ".static::humanName());
        });
    }

    private static function recordActivity(Model $model, string $action, string $description): void
    {
        $tenantId = $model->tenant_id ?? Auth::user()?->tenant_id;

        if (! $tenantId) {
            return;
        }

        $module = method_exists($model, 'auditModule') ? $model->auditModule() : 'admin';

        app(ActivityLogService::class)->record(
            Auth::user(),
            $tenantId,
            "{$module}.{$action}",
            ucfirst($description),
            RequestFacade::instance(),
            ['auditable_type' => $model::class, 'auditable_id' => $model->getKey()],
            $module
        );
    }

    private static function humanName(): string
    {
        return Str::headline(Str::snake(class_basename(static::class)));
    }
}
