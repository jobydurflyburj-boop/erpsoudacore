<?php

namespace App\Http\Middleware;

use App\Multitenancy\TenantContext;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the Super Admin Console route group. `BindAuthenticatedTenant`
 * (runs earlier in the pipeline) already sets TenantContext's
 * is_super_admin flag for a genuine platform-level token — this
 * middleware is what actually turns that flag into a 403 for anyone
 * else, the same way `CheckPermission` turns a missing tenant
 * permission into one. Deliberately NOT reusing `permission:module.action`
 * middleware here: Super Admin isn't tenant-RBAC-scoped (their role
 * holds every permission by construction), so checking a specific
 * permission would be redundant with — and weaker than — checking the
 * identity class directly.
 */
class EnsureSuperAdmin
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->isSuperAdmin()) {
            throw new AuthorizationException('This area is restricted to platform administrators.');
        }

        return $next($request);
    }
}
