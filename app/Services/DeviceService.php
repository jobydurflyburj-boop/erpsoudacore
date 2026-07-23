<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class DeviceService
{
    public function registerOrTouch(User $user, Request $request): void
    {
        $fingerprint = hash('sha256', $request->userAgent().'|'.$request->header('Sec-CH-UA-Platform', ''));

        $device = $user->devices()->where('device_fingerprint', $fingerprint)->first();

        if ($device) {
            $device->update([
                'last_ip_address' => $request->ip(),
                'last_seen_at' => now(),
            ]);

            return;
        }

        $user->devices()->create([
            'tenant_id' => $user->tenant_id,
            'device_fingerprint' => $fingerprint,
            'device_name' => \Illuminate\Support\Str::limit((string) $request->userAgent(), 60, ''),
            'platform' => $request->header('Sec-CH-UA-Platform'),
            'last_ip_address' => $request->ip(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'is_trusted' => false,
        ]);

        // TODO(notifications): dispatch a "new device signed in" email —
        // the notification channel/template is a product-content decision,
        // not core auth logic; the detection itself (this method) is complete.
    }
}
