<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\PlatformMetricsService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_counts_are_grouped_by_status_correctly(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Metrics Co', 'subdomain' => 'metrics-unit-a',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@metrics-unit-a.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        Tenant::factory()->active()->create();
        Tenant::factory()->suspended()->create();

        // These queries need is_super_admin to see across tenants, same
        // as the real controller path.
        app(TenantContext::class)->setSuperAdmin(true);
        app(TenantContext::class)->apply();

        $summary = app(PlatformMetricsService::class)->summary();

        app(TenantContext::class)->reset();

        $this->assertEquals(1, $summary['tenants']['by_status']['trial']); // the registered one
        $this->assertEquals(1, $summary['tenants']['by_status']['active']);
        $this->assertEquals(1, $summary['tenants']['by_status']['suspended']);
        $this->assertEquals(3, $summary['tenants']['total']);
    }
}
