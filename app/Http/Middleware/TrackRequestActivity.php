<?php

namespace App\Http\Middleware;

use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight "last seen" touch for the currently-used device, applied to
 * authenticated routes generally (not just login) so a device's
 * last_seen_at reflects actual usage, not just login time — used by the
 * "active sessions" UI to show which device was used most recently.
 */
class TrackRequestActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($user = $request->user()) {
            $fingerprint = hash('sha256', $request->userAgent().'|'.$request->header('Sec-CH-UA-Platform', ''));

            UserDevice::where('user_id', $user->id)
                ->where('device_fingerprint', $fingerprint)
                ->update(['last_seen_at' => now(), 'last_ip_address' => $request->ip()]);
        }

        return $response;
    }
}
