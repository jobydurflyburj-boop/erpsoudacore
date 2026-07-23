<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Every response goes through this — always wraps in {"data": ...},
     * matching the API convention in docs/FOUNDATION.md. Previously this
     * had an `is_array($data) ? $data : ['data' => $data]` special case
     * intended to let callers that had already manually wrapped avoid
     * double-wrapping — but it silently skipped wrapping for EVERY plain
     * PHP array response (message-only replies, the Platform Admin and
     * CRM dashboards' composite payloads), which is most of them. Fixed
     * to always wrap unconditionally; the handful of call sites that
     * were manually pre-wrapping were updated to stop doing that instead.
     */
    protected function ok(mixed $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status);
    }
}
