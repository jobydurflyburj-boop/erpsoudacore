<?php

namespace Tests\Feature\Reports;

use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingStatementsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounting_statement_reports_return_real_data_shapes(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Accounting Reports Co', 'subdomain' => 'acct-reports-test',
            'admin_full_name' => 'Owner', 'admin_email' => 'owner@acct-reports-test.test',
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        $tenant = $result['tenant'];
        $owner = $result['user'];

        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $income = $h()->getJson('/api/v1/reports/income-statement');
        $income->assertOk()->assertJsonStructure(['data' => ['revenue', 'total_revenue', 'expenses', 'total_expenses', 'net_income']]);

        $balanceSheet = $h()->getJson('/api/v1/reports/balance-sheet');
        $balanceSheet->assertOk()->assertJsonStructure(['data' => ['assets', 'total_assets', 'liabilities', 'total_liabilities', 'equity', 'total_equity', 'balanced']]);

        // A brand-new tenant with zero activity still balances (all zeros).
        $this->assertTrue($balanceSheet->json('data.balanced'));

        // The date-range filter on the Income Statement doesn't error on an empty range.
        $h()->getJson('/api/v1/reports/income-statement?from=2020-01-01&to=2020-12-31')->assertOk();
    }
}
