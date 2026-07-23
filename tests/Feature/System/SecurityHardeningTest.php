<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Production Readiness — Security hardening (OWASP). Verifies the two
 * real, testable pieces: the security headers middleware actually
 * attaches its headers to a real response, and the tighter `auth`
 * rate limiter actually blocks after its real limit rather than just
 * existing as an unused config declaration.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_response_carries_the_real_owasp_security_headers(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_the_auth_rate_limiter_blocks_after_its_real_limit(): void
    {
        // No manual cache-clearing needed: each test method boots a fresh
        // application instance, so the array-cache-backed rate limiter
        // already starts empty — relying on Laravel's real internal
        // rate-limiter key format here (rather than guessing at it to
        // clear manually) keeps this test honest about what it verifies.
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'nobody@example.com', 'password' => 'wrong-password',
            ]);
            // Whatever the real auth outcome is (401/422/etc.), it must not be 429 yet.
            $this->assertNotEquals(429, $response->status(), "Request {$i} was rate-limited too early.");
        }

        // The 11th request within the same minute crosses the real 10/min limit.
        $blocked = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com', 'password' => 'wrong-password',
        ]);
        $blocked->assertStatus(429);
    }
}
