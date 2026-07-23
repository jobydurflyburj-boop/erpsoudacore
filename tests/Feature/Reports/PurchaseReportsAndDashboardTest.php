<?php

namespace Tests\Feature\Reports;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseReportsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_dashboard_and_new_reports_return_real_data_shapes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Purchase Reports Co', 'subdomain' => 'purchase-reports-dash',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@purchase-reports-dash.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->getJson('/api/v1/purchase/dashboard')->assertOk()
            ->assertJsonStructure(['data' => ['document_counts', 'spend_this_month', 'outstanding_payables']]);

        $h()->getJson('/api/v1/reports/purchase-by-supplier')->assertOk();
        $h()->getJson('/api/v1/reports/aging-payables')->assertOk()
            ->assertJsonStructure(['data' => ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus']]);
    }
}
