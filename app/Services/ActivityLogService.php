<?php

namespace App\Services;

use App\Models\User;
use App\Support\Http\BrowserParser;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function record(
        ?User $user,
        string $tenantId,
        string $event,
        ?string $description = null,
        ?Request $request = null,
        array $context = [],
        ?string $module = null
    ): void {
        \App\Models\ActivityLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'event' => $event,
            'module' => $module ?? $this->inferModule($event),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'browser' => BrowserParser::parse($request?->userAgent()),
            'context' => $context,
            'created_at' => now(),
        ]);
    }

    /**
     * Falls back to the event's own namespace prefix ('auth.login' ->
     * 'auth') when no module is passed explicitly — every event name in
     * this codebase already follows that convention, so this keeps
     * existing call sites (AuthService, CheckPermission) working without
     * every one of them needing to be touched just to add a module value.
     */
    private function inferModule(string $event): string
    {
        return explode('.', $event)[0] ?? 'general';
    }
}
