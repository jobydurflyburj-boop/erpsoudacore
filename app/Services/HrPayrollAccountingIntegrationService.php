<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRun;
use App\Models\User;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * The real "Integrate HR & Payroll with Accounting" requirement —
 * mirrors SalesAccountingIntegrationService/PurchaseAccountingIntegrationService's
 * exact pattern and account-lookup safety (loud failure if a standard
 * account is missing). A processed payroll run posts one balanced
 * entry: Dr Salaries & Wages Expense (the full gross cost to the
 * business), Cr Salaries Payable (total deductions withheld — GOSI,
 * advances, etc. — pending remittance), Cr Cash (the net amount
 * actually paid out). Gross = Net + Deductions by construction (see
 * PayrollService), so this always balances.
 */
class HrPayrollAccountingIntegrationService
{
    private const CODE_CASH = '1000';
    private const CODE_SALARIES_EXPENSE = '5200';
    private const CODE_SALARIES_PAYABLE = '2200';

    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly SequenceService $sequences,
    ) {}

    public function postPayrollRunProcessed(User $actor, PayrollRun $run): JournalEntry
    {
        $cash = $this->account($actor->tenant_id, self::CODE_CASH);
        $salariesExpense = $this->account($actor->tenant_id, self::CODE_SALARIES_EXPENSE);
        $salariesPayable = $this->account($actor->tenant_id, self::CODE_SALARIES_PAYABLE);

        $lines = [
            ['account_id' => $salariesExpense->id, 'debit' => $run->total_gross, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => $run->total_net],
        ];
        if ((float) $run->total_deductions > 0) {
            $lines[] = ['account_id' => $salariesPayable->id, 'debit' => 0, 'credit' => $run->total_deductions];
        }

        return DB::transaction(function () use ($actor, $run, $lines) {
            $entry = $this->entries->create([
                'tenant_id' => $actor->tenant_id,
                'entry_number' => $this->sequences->next($actor->tenant_id, 'journal_entry_number', 'JE'),
                'entry_date' => now()->toDateString(),
                'memo' => "Payroll run {$run->run_number} ({$run->period_month}/{$run->period_year})",
                'source_type' => 'payroll_run',
                'source_id' => $run->id,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $actor->tenant_id, 'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'], 'debit' => $line['debit'], 'credit' => $line['credit'],
                ]);
            }

            return $entry->fresh('lines');
        });
    }

    private function account(string $tenantId, string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('tenant_id', $tenantId)->where('code', $code)->first();

        if (! $account) {
            throw new \RuntimeException(
                "Chart of accounts is missing standard account {$code} — payroll auto-posting cannot proceed. ".
                'This account is normally seeded at registration; if it was renamed or deleted, restore it (or run `hr:provision-defaults`) before processing a payroll run.'
            );
        }

        return $account;
    }
}
