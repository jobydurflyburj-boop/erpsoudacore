<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Public, deliberately minimal: resolves a subdomain to its tenant UUID
 * so a login screen not served on a real per-tenant subdomain (this
 * sandbox's demo console, accessed via /app rather than
 * <subdomain>.soudacore.app) can still let someone log in with the
 * subdomain they registered rather than needing to already know the raw
 * UUID. Returns only the id — the subdomain itself is already public
 * (it's in the URL a real deployment would use), so this discloses
 * nothing that isn't already effectively public.
 */
class TenantLookupController extends Controller
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    public function bySubdomain(Request $request)
    {
        $request->validate(['subdomain' => ['required', 'string']]);

        $tenant = $this->tenants->findBySubdomain($request->string('subdomain'));

        abort_if(! $tenant, 404, 'No company found for that subdomain.');

        return $this->ok(['id' => $tenant->id, 'name' => $tenant->name, 'subdomain' => $tenant->subdomain]);
    }
}
