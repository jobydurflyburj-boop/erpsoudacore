<?php

namespace App\Support\Http;

/**
 * Deliberately minimal — this is for a human-readable Activity Log
 * column ("Chrome on Windows"), not security-critical device
 * fingerprinting (that's DeviceService's sha256 fingerprint, a different
 * concern). A handful of substring checks covers the vast majority of
 * real traffic without pulling in a full user-agent-parsing dependency
 * for what is ultimately a display nicety.
 */
class BrowserParser
{
    public static function parse(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium') => 'Chrome',
            str_contains($userAgent, 'CriOS') => 'Chrome (iOS)',
            str_contains($userAgent, 'FxiOS') => 'Firefox (iOS)',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            default => 'Unknown',
        };

        $platform = match (true) {
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $platform ? "{$browser} on {$platform}" : $browser;
    }
}
