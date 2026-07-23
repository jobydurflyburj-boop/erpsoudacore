<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level RBAC gate: `->middleware('permission:admin.users.view')` —
 * wait, permission KEYS are `{module}.{action}` (e.g. `admin.view`), not
 * per-resource — see config/permissions.php. Resource-level distinction
 * (e.g. "users" vs "roles" within the admin module) is handled by which
 * controller/route the middleware guards, not by the permission key
 * itself, keeping the permission catalog small and stable as resources
 * are added within a module.
 *
 * This is the single enforcement point the whole RBAC system funnels
 * through — the frontend hides controls the user can't use for UX, but
 * this middleware is what actually stops the request.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new \Illuminate\Auth\AuthenticationException;
        }

        [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);

        if (! $user->role || ! $user->role->hasPermission($module, $action)) {
            \App\Models\ActivityLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'event' => 'rbac.permission_denied',
                'description' => "Denied access to {$permission}.",
                'ip_address' => $request->ip(),
                'context' => ['permission' => $permission],
                'created_at' => now(),
            ]);

            throw new AuthorizationException("You do not have permission to perform this action ({$permission}).");
        }

        return $next($request);
    }
}
