<?php

namespace Tests\Feature\Tenancy;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Accounting Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);
        return [$result['tenant'], $result['user']];
    }

    public function test_journal_entries_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('acct-ext-iso-a');
        [$tenantB] = $this->registerTenant('acct-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        JournalEntry::create([
            'tenant_id' => $tenantB->id, 'entry_number' => 'JE-777777', 'entry_date' => now()->toDateString(), 'memo' => 'Hidden entry',
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('journal_entries')->where('entry_number', 'JE-777777')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_the_vat_recoverable_account_is_a_distinct_row_per_tenant(): void
    {
        [$tenantA] = $this->registerTenant('acct-ext-iso-vat-a');
        [$tenantB] = $this->registerTenant('acct-ext-iso-vat-b');

        $vatRecoverableA = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantA->id)->where('code', '2110')->firstOrFail();
        $vatRecoverableB = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantB->id)->where('code', '2110')->firstOrFail();

        $this->assertNotEquals($vatRecoverableA->id, $vatRecoverableB->id);
    }

    public function test_journal_entry_numbers_are_independent_per_tenant_after_a_reversal(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('acct-je-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('acct-je-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $cashA = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantA->id)->where('code', '1000')->firstOrFail();
        $equityA = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantA->id)->where('code', '3000')->firstOrFail();
        $entryA = app(\App\Services\AccountingService::class)->createEntry($ownerA, [
            'lines' => [['account_id' => $cashA->id, 'debit' => 100, 'credit' => 0], ['account_id' => $equityA->id, 'debit' => 0, 'credit' => 100]],
        ]);
        $reversalA = app(\App\Services\AccountingService::class)->reverseEntry($ownerA, $entryA);

        $context->set($tenantB);
        $context->apply();
        $cashB = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantB->id)->where('code', '1000')->firstOrFail();
        $equityB = ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenantB->id)->where('code', '3000')->firstOrFail();
        $entryB = app(\App\Services\AccountingService::class)->createEntry($ownerB, [
            'lines' => [['account_id' => $cashB->id, 'debit' => 100, 'credit' => 0], ['account_id' => $equityB->id, 'debit' => 0, 'credit' => 100]],
        ]);
        $reversalB = app(\App\Services\AccountingService::class)->reverseEntry($ownerB, $entryB);

        $context->reset();

        // Each tenant's sequence starts fresh: entry 1 then reversal 2, independently.
        $this->assertEquals($entryA->entry_number, $entryB->entry_number);
        $this->assertEquals($reversalA->entry_number, $reversalB->entry_number);
    }
}
