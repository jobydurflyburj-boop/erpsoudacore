<?php

namespace Tests\Feature\Reports;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportsMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_report_endpoints_return_real_data_shapes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Reports Test Co', 'subdomain' => 'reports-mvp',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@reports-mvp.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->getJson('/api/v1/reports/sales')->assertOk()->assertJsonStructure(['data' => ['total_invoiced', 'total_collected', 'total_outstanding']]);
        $h()->getJson('/api/v1/reports/purchases')->assertOk()->assertJsonStructure(['data' => ['total_ordered']]);
        $h()->getJson('/api/v1/reports/inventory')->assertOk()->assertJsonStructure(['data' => ['total_products', 'total_stock_value']]);
        $h()->getJson('/api/v1/reports/trial-balance')->assertOk()->assertJsonStructure(['data' => ['accounts', 'total_debit', 'total_credit']]);
    }
}
