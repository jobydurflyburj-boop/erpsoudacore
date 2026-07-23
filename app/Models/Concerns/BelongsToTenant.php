<?php

namespace App\Models\Concerns;

use App\Multitenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Application-layer half of tenant isolation. PostgreSQL RLS (see the
 * enable_row_level_security migration) is the enforcement boundary that
 * makes cross-tenant access impossible even if this trait is misused;
 * this trait's job is convenience and correctness of *writes* — without
 * it, every `Model::create()` call would need `tenant_id` passed
 * explicitly, which is exactly the kind of easy-to-forget step RLS exists
 * to protect against, but forgetting it would still mean a row with the
 * WRONG tenant_id gets written successfully (RLS's WITH CHECK would only
 * catch a tenant_id that doesn't match the session at all, not a missing
 * one defaulting incorrectly). Belt and suspenders.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->id();

                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
