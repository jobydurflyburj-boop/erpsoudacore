<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingMvpTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Accounting Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_registration_provisions_a_default_chart_of_accounts(): void
    {
        [$tenant] = $this->registerTenantWithOwner('acct-provision');
        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '1000', 'type' => 'asset']);
        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '4000', 'type' => 'revenue']);
    }

    public function test_a_balanced_journal_entry_is_accepted(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-balanced');
        Sanctum::actingAs($owner);

        $cash = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '1000')->first();
        $revenue = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '4000')->first();

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/accounting/journal-entries', [
            'memo' => 'Cash sale',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertCreated();
        $this->assertMatchesRegularExpression('/^JE-\d{6}$/', $response->json('data.entry_number'));
    }

    public function test_an_unbalanced_journal_entry_is_rejected(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-unbalanced');
        Sanctum::actingAs($owner);

        $cash = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '1000')->first();
        $revenue = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '4000')->first();

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/accounting/journal-entries', [
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 900],
            ],
        ]);

        $response->assertStatus(422);
    }
}
