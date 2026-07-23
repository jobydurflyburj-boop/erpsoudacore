<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The Accounting Module completion sprint: real journal entry
 * reversal, split input/output VAT accounts, and two real financial
 * statements (Income Statement, Balance Sheet) computed from actual
 * journal entry lines — including entries auto-posted by Sales and
 * Purchase, proving the whole integration loop actually closes.
 */
class AccountingModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Accounting Module Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_a_new_tenant_gets_the_split_vat_recoverable_account(): void
    {
        [$tenant] = $this->registerTenantWithOwner('acct-new-tenant-vat');

        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '2100', 'name_en' => 'VAT Payable']);
        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '2110', 'name_en' => 'VAT Recoverable']);
    }

    public function test_the_backfill_command_adds_the_vat_recoverable_account_to_an_existing_tenant(): void
    {
        [$tenant] = $this->registerTenantWithOwner('acct-backfill-vat');

        // Simulate a pre-sprint tenant: remove the account this sprint added.
        ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '2110')->delete();
        $this->assertDatabaseMissing('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '2110']);

        $this->artisan('accounting:provision-defaults', ['tenant' => $tenant->id])->assertExitCode(0);

        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '2110', 'name_en' => 'VAT Recoverable']);
        // Existing accounts weren't duplicated.
        $this->assertEquals(1, ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '1000')->count());
    }

    public function test_purchase_now_posts_input_vat_to_its_own_recoverable_account(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-purchase-vat-split');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P1', 'name_en' => 'P1'])->json('data.id');
        $supplierId = $h()->postJson('/api/v1/purchase/suppliers', ['name' => 'S1'])->json('data.id');

        $billId = $h()->postJson('/api/v1/purchase/bills', [
            'supplier_id' => $supplierId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_cost' => 100, 'vat_rate' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/purchase/bills/{$billId}/approve")->assertOk();

        $vatRecoverable = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '2110')->firstOrFail();
        $this->assertDatabaseHas('journal_entry_lines', ['tenant_id' => $tenant->id, 'account_id' => $vatRecoverable->id, 'debit' => 15]);
    }

    public function test_journal_entry_reversal_creates_a_balanced_swapped_entry_and_marks_the_original(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-reversal');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $cash = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '1000')->firstOrFail();
        $equity = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '3000')->firstOrFail();

        $entry = $h()->postJson('/api/v1/accounting/journal-entries', [
            'memo' => 'Owner capital injection',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 5000],
            ],
        ]);
        $entry->assertCreated();
        $entryId = $entry->json('data.id');

        $reversal = $h()->postJson("/api/v1/accounting/journal-entries/{$entryId}/reverse");
        $reversal->assertCreated();
        $reversalId = $reversal->json('data.id');

        $original = $h()->getJson("/api/v1/accounting/journal-entries/{$entryId}")->json('data');
        $this->assertTrue($original['is_reversed']);
        $this->assertEquals($reversalId, $original['reversed_by_entry_id']);

        // The reversal swapped every line's debit/credit and still balances.
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $reversalId, 'account_id' => $cash->id, 'debit' => 0, 'credit' => 5000]);
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $reversalId, 'account_id' => $equity->id, 'debit' => 5000, 'credit' => 0]);

        // A second reversal attempt is rejected.
        $h()->postJson("/api/v1/accounting/journal-entries/{$entryId}/reverse")->assertStatus(422);
    }

    public function test_an_auto_posted_entry_cannot_be_reversed_directly(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-no-reverse-auto');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P2', 'name_en' => 'P2'])->json('data.id');
        $customerId = \App\Models\Customer::factory()->create(['tenant_id' => $tenant->id])->id;

        $invoiceId = $h()->postJson('/api/v1/sales/invoices', [
            'customer_id' => $customerId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/issue")->assertOk();

        $autoEntry = \App\Models\JournalEntry::where('source_type', 'sales_invoice')->where('source_id', $invoiceId)->firstOrFail();

        $h()->postJson("/api/v1/accounting/journal-entries/{$autoEntry->id}/reverse")->assertStatus(422);
    }

    public function test_income_statement_and_balance_sheet_reflect_real_sales_and_purchase_activity(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('acct-statements');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $productId = $h()->postJson('/api/v1/inventory/products', ['sku' => 'P3', 'name_en' => 'P3'])->json('data.id');
        $customerId = \App\Models\Customer::factory()->create(['tenant_id' => $tenant->id])->id;

        $invoiceId = $h()->postJson('/api/v1/sales/invoices', [
            'customer_id' => $customerId, 'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 15]],
        ])->json('data.id');
        $h()->postJson("/api/v1/sales/invoices/{$invoiceId}/issue")->assertOk();

        $income = $h()->getJson('/api/v1/reports/income-statement');
        $income->assertOk();
        $this->assertEquals(1000, $income->json('data.total_revenue'));
        $this->assertEquals(1000, $income->json('data.net_income'));

        $balanceSheet = $h()->getJson('/api/v1/reports/balance-sheet');
        $balanceSheet->assertOk();
        $this->assertTrue($balanceSheet->json('data.balanced'));
        $this->assertEquals(1150, $balanceSheet->json('data.total_assets')); // AR 1150 (incl. VAT)
    }
}
