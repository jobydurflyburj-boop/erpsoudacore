<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * The real "Integrate Purchase with Accounting" requirement this
 * project has been missing since the Sales and Inventory sprints built
 * their own equivalents — the liability side (Accounts Payable) that
 * neither Purchase Orders nor Goods Receipts create. Mirrors
 * SalesAccountingIntegrationService's exact pattern and account-lookup
 * safety (loud failure if a standard account is missing).
 *
 * As of the Accounting Module completion sprint, input VAT posts to
 * its own account (2110 VAT Recoverable) rather than netting through
 * Sales' 2100 VAT Payable — the split the Purchase sprint's own
 * documentation named as a deliberate, temporary simplification.
 */
class PurchaseAccountingIntegrationService
{
    private const CODE_CASH = '1000';
    private const CODE_INVENTORY = '1200';
    private const CODE_AP = '2000';
    private const CODE_VAT_RECOVERABLE = '2110';

    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly SequenceService $sequences,
    ) {}

    /**
     * A Supplier Bill approved: Dr Inventory (the goods received), Dr
     * VAT Recoverable (input tax, its own account as of this sprint —
     * previously netted through Sales' VAT Payable account), Cr
     * Accounts Payable.
     */
    public function postBillApproved(User $actor, SupplierBill $bill): JournalEntry
    {
        $inventory = $this->account($actor->tenant_id, self::CODE_INVENTORY);
        $ap = $this->account($actor->tenant_id, self::CODE_AP);
        $vat = $this->account($actor->tenant_id, self::CODE_VAT_RECOVERABLE);

        $lines = [
            ['account_id' => $inventory->id, 'debit' => (float) $bill->subtotal, 'credit' => 0],
        ];
        if ((float) $bill->vat_amount > 0) {
            $lines[] = ['account_id' => $vat->id, 'debit' => (float) $bill->vat_amount, 'credit' => 0];
        }
        $lines[] = ['account_id' => $ap->id, 'debit' => 0, 'credit' => (float) $bill->total];

        return $this->post($actor, $lines, "Supplier bill {$bill->document_number} approved", 'supplier_bill', $bill->id);
    }

    /** A payment made to a supplier: Dr Accounts Payable, Cr Cash. */
    public function postPaymentMade(User $actor, SupplierPayment $payment, float $amount): JournalEntry
    {
        $ap = $this->account($actor->tenant_id, self::CODE_AP);
        $cash = $this->account($actor->tenant_id, self::CODE_CASH);

        return $this->post($actor, [
            ['account_id' => $ap->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => $amount],
        ], "Payment {$payment->payment_number} made", 'supplier_payment', $payment->id);
    }

    /** A debit note issued: Dr Accounts Payable, Cr Inventory / Cr VAT — the exact reverse of the original bill posting. */
    public function postDebitNoteIssued(User $actor, DebitNote $debitNote): JournalEntry
    {
        $inventory = $this->account($actor->tenant_id, self::CODE_INVENTORY);
        $ap = $this->account($actor->tenant_id, self::CODE_AP);
        $vat = $this->account($actor->tenant_id, self::CODE_VAT_RECOVERABLE);

        $lines = [
            ['account_id' => $ap->id, 'debit' => (float) $debitNote->total, 'credit' => 0],
            ['account_id' => $inventory->id, 'debit' => 0, 'credit' => (float) $debitNote->subtotal],
        ];
        if ((float) $debitNote->vat_amount > 0) {
            $lines[] = ['account_id' => $vat->id, 'debit' => 0, 'credit' => (float) $debitNote->vat_amount];
        }

        return $this->post($actor, $lines, "Debit note {$debitNote->document_number} issued", 'debit_note', $debitNote->id);
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
                "Chart of accounts is missing standard account {$code} — purchase auto-posting cannot proceed. ".
                'This account is normally seeded at registration; if it was renamed or deleted, restore it before approving bills, payments, or debit notes.'
            );
        }

        return $account;
    }
}
