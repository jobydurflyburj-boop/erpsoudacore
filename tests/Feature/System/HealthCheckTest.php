<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The real deep health check (distinct from Laravel's built-in `/up`)
 * — verifies it's genuinely public (no auth/tenant header required,
 * since a load balancer can't authenticate) and reports real
 * component status rather than just a static "ok".
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_is_public_and_reports_real_component_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonStructure(['status', 'checks' => ['database', 'cache', 'queue'], 'timestamp']);
        // The database really is reachable in this test environment (RefreshDatabase
        // wouldn't work otherwise) — a real assertion, not just "the endpoint exists".
        $this->assertEquals('ok', $response->json('checks.database'));
    }

    public function test_health_check_requires_no_tenant_context(): void
    {
        // Deliberately no X-Tenant-ID header and no auth token — a load balancer
        // can't supply either, and the endpoint must still respond correctly.
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
    }
}
