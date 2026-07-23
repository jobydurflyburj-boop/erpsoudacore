<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * A real, deep health check — distinct from Laravel's own built-in
 * `/up` (bootstrap/app.php's `health: '/up'`), which only confirms
 * the application booted. This checks the actual dependencies a
 * production deployment cares about: can it reach the real database,
 * can it reach the real cache/queue backend (Redis). Deliberately
 * public (no auth) — a load balancer or uptime monitor can't
 * authenticate, and the response reveals nothing sensitive (no
 * versions, no config, no stack traces), only up/down per component.
 */
class HealthCheckController extends Controller
{
    public function index()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => array_map(fn ($ok) => $ok ? 'ok' : 'unavailable', $checks),
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health-check-'.now()->timestamp;
            Cache::put($key, true, 5);

            return Cache::get($key) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            // A real connectivity check against the configured queue
            // connection (Redis in production) — doesn't dispatch a
            // real job, just confirms the connection resolves and can
            // be talked to, the same way the cache check above does.
            Queue::connection()->size('health-check');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
