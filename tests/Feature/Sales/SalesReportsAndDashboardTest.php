<?php

namespace Tests\Feature\Sales;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesReportsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_dashboard_and_new_reports_return_real_data_shapes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Sales Reports Co', 'subdomain' => 'sales-reports-dash',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@sales-reports-dash.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->getJson('/api/v1/sales/dashboard')->assertOk()
            ->assertJsonStructure(['data' => ['document_counts', 'quotation_conversion_rate', 'revenue_this_month', 'outstanding_receivables']]);

        $h()->getJson('/api/v1/reports/sales-by-customer')->assertOk();
        $h()->getJson('/api/v1/reports/sales-by-product')->assertOk();
        $h()->getJson('/api/v1/reports/aging-receivables')->assertOk()
            ->assertJsonStructure(['data' => ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus']]);
    }
}
