<?php

use App\Http\Middleware\BindAuthenticatedTenant;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackRequestActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Every real page of this product is a JSON API consumed by an
        // external frontend — routes/web.php exists for exactly one
        // thing: serving the single static Super Admin Console shell
        // (resources/views/super-admin/console.blade.php), which itself
        // only ever talks to the same v1 JSON API via fetch() with a
        // bearer token. This is not a server-rendered application and
        // no session/CSRF-dependent form ever posts to a 'web' route —
        // Laravel's default 'web' group is otherwise unused here.
        $middleware->api(prepend: [
            ResolveTenant::class,
        ]);

        // Applied to both groups — the JSON API is fetched directly by
        // browsers (no separate server-rendered frontend exists), and
        // the Super Admin Console's one HTML shell lives on `web` — so
        // both surfaces get the same OWASP baseline headers.
        $middleware->api(append: [SecurityHeaders::class]);
        $middleware->web(append: [SecurityHeaders::class]);

        $middleware->alias([
            'tenant.active' => EnsureTenantIsActive::class,
            'tenant.bind_authenticated' => BindAuthenticatedTenant::class,
            'ensure.super_admin' => EnsureSuperAdmin::class,
            'permission' => CheckPermission::class,
            'track.activity' => TrackRequestActivity::class,
        ]);

        $middleware->throttleApi('api');

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Single JSON error envelope for every exception type — see
        // docs/FOUNDATION.md "API error format". Never leaks a stack
        // trace or raw exception message in production.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        // Production Readiness — Error tracking: every exception Laravel
        // already reports (via report($e), called explicitly in the
        // catch-all render() below, and automatically for any exception
        // that isn't caught by a more specific render() callback) is
        // now also sent to App\Services\ErrorTrackingService — a real
        // webhook delivery, not just the application log, when one is
        // configured. reportable() runs in addition to Laravel's normal
        // logging, never replacing it.
        $exceptions->reportable(function (\Throwable $e) {
            app(\App\Services\ErrorTrackingService::class)->report($e);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'error' => 'validation_failed',
                'message' => 'The given data was invalid.',
                'details' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'Authentication is required to access this resource.',
                'details' => (object) [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'error' => 'forbidden',
                'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
                'details' => (object) [],
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'The requested resource was not found.',
                'details' => (object) [],
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            return response()->json([
                'error' => Str_error_slug($e->getStatusCode()),
                'message' => $e->getMessage() ?: 'An error occurred.',
                'details' => (object) [],
            ], $e->getStatusCode());
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            report($e);

            return response()->json([
                'error' => 'server_error',
                'message' => app()->isProduction()
                    ? 'An unexpected error occurred. Our team has been notified.'
                    : $e->getMessage(),
                'details' => app()->isProduction() ? (object) [] : [
                    'exception' => get_class($e),
                    'file' => $e->getFile().':'.$e->getLine(),
                ],
            ], 500);
        });
    })->create();

if (! function_exists('Str_error_slug')) {
    function Str_error_slug(int $status): string
    {
        return match ($status) {
            409 => 'conflict',
            429 => 'too_many_requests',
            403 => 'forbidden',
            401 => 'unauthenticated',
            422 => 'validation_failed',
            default => 'http_error',
        };
    }
}
