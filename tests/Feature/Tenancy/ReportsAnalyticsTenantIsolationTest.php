<?php

namespace Tests\Feature\Tenancy;

use App\Models\CustomReport;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsAnalyticsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Reports Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_custom_reports_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('ra-ext-iso-a');
        [$tenantB] = $this->registerTenant('ra-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        CustomReport::create([
            'tenant_id' => $tenantB->id, 'name' => 'Hidden Report', 'source' => 'employees', 'columns' => ['id'],
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('custom_reports')->where('name', 'Hidden Report')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }
}
