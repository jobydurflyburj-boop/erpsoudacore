<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\GoodsIssue;
use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * The real "Integrate Inventory with Accounting" requirement, mirroring
 * SalesAccountingIntegrationService's pattern exactly: every inventory
 * event with real financial value posts a balanced journal entry
 * against the tenant's actual chart of accounts (1200 Inventory, 5100
 * Operating Expenses). Goods Receipts don't post here — receiving
 * against a Purchase Order is a liability event (Accounts Payable),
 * which belongs to Purchase-side accounting integration, explicitly
 * out of scope for this sprint (see docs/INVENTORY_MODULE_SPRINT.md).
 */
class InventoryAccountingIntegrationService
{
    private const CODE_INVENTORY = '1200';
    private const CODE_EXPENSES = '5100';

    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly SequenceService $sequences,
    ) {}

    /**
     * A stock adjustment with real cost impact: a negative adjustment
     * (shrinkage/write-off) is Dr Expense / Cr Inventory; a positive
     * one (found stock) is the reverse. Valued at each product's own
     * cost_price — the same valuation basis Inventory's own valuation
     * report already uses.
     */
    public function postStockAdjustment(User $actor, StockAdjustment $adjustment, float $totalValueChange): ?JournalEntry
    {
        if (abs($totalValueChange) < 0.005) {
            return null; // a zero-value adjustment (e.g. pure quantity relabeling at zero cost) has nothing to post
        }

        $inventory = $this->account($actor->tenant_id, self::CODE_INVENTORY);
        $expense = $this->account($actor->tenant_id, self::CODE_EXPENSES);
        $amount = abs($totalValueChange);

        $lines = $totalValueChange > 0
            ? [ // found stock: inventory value increases
                ['account_id' => $inventory->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $expense->id, 'debit' => 0, 'credit' => $amount],
            ]
            : [ // shrinkage/write-off: inventory value decreases, expensed
                ['account_id' => $expense->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $inventory->id, 'debit' => 0, 'credit' => $amount],
            ];

        return $this->post($actor, $lines, "Stock adjustment {$adjustment->document_number} approved", 'stock_adjustment', $adjustment->id);
    }

    /** Goods issued for internal use/consumption: Dr Expense, Cr Inventory — always a cost, valued at product cost_price. */
    public function postGoodsIssue(User $actor, GoodsIssue $issue, float $totalCost): ?JournalEntry
    {
        if ($totalCost < 0.005) {
            return null;
        }

        $inventory = $this->account($actor->tenant_id, self::CODE_INVENTORY);
        $expense = $this->account($actor->tenant_id, self::CODE_EXPENSES);

        return $this->post($actor, [
            ['account_id' => $expense->id, 'debit' => $totalCost, 'credit' => 0],
            ['account_id' => $inventory->id, 'debit' => 0, 'credit' => $totalCost],
        ], "Goods issue {$issue->document_number}", 'goods_issue', $issue->id);
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
                "Chart of accounts is missing standard account {$code} — inventory auto-posting cannot proceed. ".
                'This account is normally seeded at registration; if it was renamed or deleted, restore it before approving stock adjustments or goods issues.'
            );
        }

        return $account;
    }
}
