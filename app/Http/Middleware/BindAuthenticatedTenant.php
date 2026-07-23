<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs immediately after auth:sanctum on every authenticated route.
 * Without this, tenant binding and token identity are only related by
 * coincidence: ResolveTenant (global middleware) resolves a tenant from
 * the subdomain/header BEFORE Sanctum has verified who's calling, so
 * nothing has actually confirmed the authenticated user belongs to the
 * tenant the request resolved to.
 *
 * In practice, BelongsToTenant's global scope + RLS already prevent a
 * cross-tenant token from *reading another tenant's data* (a mismatched
 * lookup returns zero rows — see TenantIsolationTest and
 * CrossTenantTokenTest). But relying on that as the ONLY guard has two
 * problems this middleware closes:
 *
 *   1. It fails as a confusing 401 (user "not found") rather than a
 *      clear, intentional 403 — worth being explicit about.
 *   2. If a route is ever added under auth:sanctum WITHOUT tenant.active
 *      (which requires a resolved tenant), and the request hit the
 *      central domain with no X-Tenant-ID header, TenantContext has NO
 *      tenant bound at all — and BelongsToTenant's global scope
 *      deliberately no-ops when no tenant is bound (see that trait's
 *      comment), meaning EVERY subsequent tenant-scoped query in that
 *      request would run genuinely unscoped at the application layer
 *      (RLS would still block cross-tenant SELECTs, but an INSERT could
 *      write a wrong/missing tenant_id). This middleware removes that
 *      possibility by always binding TenantContext to the authenticated
 *      user's own tenant when nothing else already resolved one.
 */
class BindAuthenticatedTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request); // no authenticated user — nothing to bind, unauthenticated guard handles it downstream
        }

        $isSuperAdmin = $user->tenant_id === null && $user->role?->code === \App\Models\Role::SUPER_ADMIN;

        if ($isSuperAdmin) {
            $this->context->setSuperAdmin(true);
            $this->context->apply();

            return $next($request);
        }

        if ($this->context->hasTenant()) {
            // A tenant WAS resolved (subdomain/header) — it must match
            // the token's own tenant. A mismatch means a token issued
            // for one company is being presented against another
            // company's subdomain — reject explicitly rather than let it
            // surface as an accidental lookup failure.
            if ($this->context->id() !== $user->tenant_id) {
                throw new AuthorizationException('This session does not belong to this company.');
            }

            return $next($request);
        }

        // No tenant resolved from subdomain/header (e.g. a request that
        // reached the central domain) — fall back to the authenticated
        // user's own tenant so every query in this request is still
        // correctly bound, rather than running unscoped.
        $tenant = Tenant::find($user->tenant_id);

        if ($tenant) {
            $this->context->set($tenant);
            $this->context->apply();
        }

        return $next($request);
    }
}
