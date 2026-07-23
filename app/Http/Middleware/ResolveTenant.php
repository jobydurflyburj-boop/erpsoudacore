<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs first on every API request (see bootstrap/app.php). Determines
 * which tenant (if any) this request belongs to, from the subdomain in
 * production or the X-Tenant-ID header in local/dev/tests
 * (config/tenancy.php), and binds it into TenantContext for the rest of
 * the request lifecycle — including the Postgres session variables the
 * RLS policies depend on (TenantContext::apply()).
 *
 * Requests to the central domain (registration, Super Admin console,
 * billing webhooks) resolve NO tenant here — that's expected, not an
 * error; those routes don't need one, or resolve it explicitly from the
 * request payload (e.g. the tenant a webhook's invoice belongs to).
 */
class ResolveTenant
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveFromHeader($request) ?? $this->resolveFromSubdomain($request);

        if ($tenant) {
            $this->context->set($tenant);
        }

        $this->context->apply();

        return $next($request);
    }

    private function resolveFromHeader(Request $request): ?Tenant
    {
        if (! config('tenancy.resolution.header')) {
            return null;
        }

        $tenantId = $request->header(config('tenancy.resolution.header_name'));

        if (! $tenantId) {
            return null;
        }

        return $this->tenants->find($tenantId);
    }

    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        if (! config('tenancy.resolution.subdomain')) {
            return null;
        }

        $host = $request->getHost();
        $central = config('tenancy.central_domain');

        if ($host === $central || ! str_ends_with($host, '.'.$central)) {
            return null; // central domain request — no tenant
        }

        $subdomain = substr($host, 0, -1 * (strlen($central) + 1));

        return $this->tenants->findBySubdomain($subdomain);
    }
}
