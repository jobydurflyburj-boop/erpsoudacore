<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\CreditNote;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * The real "Integrate Sales with Accounting" requirement: every
 * financial event on the sales side auto-posts a real, balanced
 * journal entry against the tenant's own chart of accounts — using the
 * standard codes AccountingProvisioningService seeds (1000 Cash, 1100
 * AR, 2100 VAT Payable, 4000 Sales Revenue). If a tenant has renamed or
 * removed those codes, posting fails loudly (an unresolvable account is
 * a real configuration problem, not something to silently skip).
 *
 * Deliberately a separate service from AccountingService — that one is
 * the general-purpose, manually-invoked journal entry API; this one is
 * sales-domain logic that happens to produce journal entries as a
 * side effect, called from Sales services, not from the Accounting
 * controller.
 */
class SalesAccountingIntegrationService
{
    private const CODE_CASH = '1000';
    private const CODE_AR = '1100';
    private const CODE_VAT_PAYABLE = '2100';
    private const CODE_REVENUE = '4000';

    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly SequenceService $sequences,
    ) {}

    /** Invoice issued: Dr Accounts Receivable, Cr Sales Revenue, Cr VAT Payable. */
    public function postInvoiceIssued(User $actor, SalesInvoice $invoice): JournalEntry
    {
        $ar = $this->account($actor->tenant_id, self::CODE_AR);
        $revenue = $this->account($actor->tenant_id, self::CODE_REVENUE);
        $vat = $this->account($actor->tenant_id, self::CODE_VAT_PAYABLE);

        $lines = [
            ['account_id' => $ar->id, 'debit' => (float) $invoice->total, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => (float) $invoice->subtotal],
        ];
        if ((float) $invoice->vat_amount > 0) {
            $lines[] = ['account_id' => $vat->id, 'debit' => 0, 'credit' => (float) $invoice->vat_amount];
        }

        return $this->post($actor, $lines, "Invoice {$invoice->document_number} issued", 'sales_invoice', $invoice->id);
    }

    /** Payment received: Dr Cash, Cr Accounts Receivable. */
    public function postPaymentReceived(User $actor, CustomerPayment $payment, float $amount): JournalEntry
    {
        $cash = $this->account($actor->tenant_id, self::CODE_CASH);
        $ar = $this->account($actor->tenant_id, self::CODE_AR);

        return $this->post($actor, [
            ['account_id' => $cash->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $ar->id, 'debit' => 0, 'credit' => $amount],
        ], "Payment {$payment->payment_number} received", 'customer_payment', $payment->id);
    }

    /** Credit note issued: Dr Sales Revenue, Dr VAT Payable, Cr Accounts Receivable — the exact reverse of the original invoice posting. */
    public function postCreditNoteIssued(User $actor, CreditNote $creditNote): JournalEntry
    {
        $ar = $this->account($actor->tenant_id, self::CODE_AR);
        $revenue = $this->account($actor->tenant_id, self::CODE_REVENUE);
        $vat = $this->account($actor->tenant_id, self::CODE_VAT_PAYABLE);

        $lines = [
            ['account_id' => $revenue->id, 'debit' => (float) $creditNote->subtotal, 'credit' => 0],
        ];
        if ((float) $creditNote->vat_amount > 0) {
            $lines[] = ['account_id' => $vat->id, 'debit' => (float) $creditNote->vat_amount, 'credit' => 0];
        }
        $lines[] = ['account_id' => $ar->id, 'debit' => 0, 'credit' => (float) $creditNote->total];

        return $this->post($actor, $lines, "Credit note {$creditNote->document_number} issued", 'credit_note', $creditNote->id);
    }

    private function post(User $actor, array $lines, string $memo, string $sourceType, string $sourceId): JournalEntry
    {
        return DB::transaction(function () use ($actor, $lines, $memo, $sourceType, $sourceId) {
            $entry = $this->entries->create([
                'tenant_id' => $actor->tenant_id,
                'entry_number' => $this->sequences->next($actor->tenant_id, 'journal_entry_number', 'JE'),
                'entry_date' => now()->toDateString(),
                'memo' => $memo,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($lines as $line) {
                \App\Models\JournalEntryLine::create([
                    'tenant_id' => $actor->tenant_id,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
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
                "Chart of accounts is missing standard account {$code} — sales auto-posting cannot proceed. ".
                "This account is normally seeded at registration; if it was renamed or deleted, restore it before issuing sales documents."
            );
        }

        return $account;
    }
}
