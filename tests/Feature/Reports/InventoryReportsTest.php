<?php

namespace Tests\Feature\Reports;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_inventory_reports_return_real_data_shapes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Inventory Reports Co', 'subdomain' => 'inv-reports-test',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@inv-reports-test.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->getJson('/api/v1/reports/stock-by-warehouse')->assertOk();
        $h()->getJson('/api/v1/reports/inventory-by-category')->assertOk();
        $h()->getJson('/api/v1/inventory/low-stock')->assertOk();
    }
}
