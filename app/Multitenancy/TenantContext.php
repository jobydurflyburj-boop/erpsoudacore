<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Request-scoped holder for "which tenant is this request for". Bound as
 * a singleton per-request (see AppServiceProvider) — resolved once by
 * ResolveTenant middleware, read by BelongsToTenant, CheckPermission, and
 * anything else that needs to know the current tenant.
 *
 * apply() is what actually binds the Postgres session variables the RLS
 * policies check (current_tenant_id() / is_super_admin() — see the
 * enable_row_level_security migration). This MUST run before any other
 * query touches a tenant-scoped table in this request.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    private bool $isSuperAdmin = false;

    private bool $applied = false;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->applied = false;
    }

    public function setSuperAdmin(bool $value = true): void
    {
        $this->isSuperAdmin = $value;
        $this->applied = false;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?string
    {
        return $this->tenant?->id;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Binds app.tenant_id / app.is_super_admin for the current DB
     * connection. set_config(..., true) scopes it to the current
     * transaction in Postgres < 14 semantics for LOCAL, but we use the
     * session-level (is_local = false is what we actually want across a
     * whole request, not just one transaction) — see note below.
     */
    public function apply(): void
    {
        if ($this->applied) {
            return;
        }

        // is_local = false: persists for the whole DB session (i.e. the
        // whole request, since we use one connection per request), not
        // just the current transaction — several service-layer calls in
        // one request each open their own transaction and all need the
        // same tenant binding.
        DB::statement('SELECT set_config(?, ?, false)', [
            'app.tenant_id',
            $this->tenant?->id ?? '',
        ]);

        DB::statement('SELECT set_config(?, ?, false)', [
            'app.is_super_admin',
            $this->isSuperAdmin ? 'true' : 'false',
        ]);

        $this->applied = true;
    }

    public function reset(): void
    {
        $this->tenant = null;
        $this->isSuperAdmin = false;
        $this->applied = false;
    }
}
