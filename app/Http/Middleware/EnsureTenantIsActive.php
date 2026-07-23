<?php

namespace App\Http\Middleware;

use App\Multitenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to routes that require a resolved, billable-and-in-good-standing
 * tenant (i.e. everything except registration/public/Super Admin routes).
 * A `past_due` tenant is still allowed through (with a warning the
 * frontend surfaces) — only `suspended`/`cancelled` block access, per the
 * billing lifecycle in docs/FOUNDATION.md §8.
 */
class EnsureTenantIsActive
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->hasTenant()) {
            return response()->json([
                'error' => 'tenant_not_resolved',
                'message' => 'No company could be resolved for this request.',
                'details' => (object) [],
            ], 400);
        }

        $tenant = $this->context->tenant();

        if (! $tenant->isActive()) {
            return response()->json([
                'error' => 'tenant_inactive',
                'message' => "This company account is currently {$tenant->status}.",
                'details' => ['status' => $tenant->status],
            ], 403);
        }

        return $next($request);
    }
}
