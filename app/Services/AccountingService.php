<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly SequenceService $sequences,
    ) {}

    /**
     * @param array $lines each: ['account_id'=>, 'debit'=>, 'credit'=>]
     * Real double-entry validation: total debits must equal total
     * credits, and every line must have exactly one of debit/credit
     * non-zero — rejected outright otherwise, not auto-corrected.
     */
    public function createEntry(User $actor, array $data): JournalEntry
    {
        $lines = $data['lines'];
        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if ($totalDebit !== $totalCredit) {
            throw new InvalidArgumentException("Journal entry is unbalanced: debits ({$totalDebit}) must equal credits ({$totalCredit}).");
        }

        if ($totalDebit == 0.0) {
            throw new InvalidArgumentException('A journal entry cannot have zero total value.');
        }

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if (($debit > 0) === ($credit > 0)) {
                throw new InvalidArgumentException('Each journal line must have either a debit or a credit, not both or neither.');
            }
        }

        return DB::transaction(function () use ($actor, $data, $lines) {
            $entry = $this->entries->create([
                'tenant_id' => $actor->tenant_id,
                'entry_number' => $this->sequences->next($actor->tenant_id, 'journal_entry_number', 'JE'),
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'memo' => $data['memo'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $actor->tenant_id,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $entry->fresh('lines');
        });
    }

    /**
     * Real journal entry reversal — the manual-entry engine's missing
     * piece since Foundation. A reversed entry is never edited or
     * deleted in place (that would break the audit trail every other
     * part of this project takes seriously); instead a brand-new
     * entry is created with every line's debit/credit swapped, and the
     * original is marked reversed and linked to it. Auto-posted
     * entries (source_type set — from Sales/Purchase/Inventory
     * integrations) cannot be reversed here: correcting those means
     * correcting the sales/purchase/inventory document that caused
     * them, not editing the accounting side in isolation.
     */
    public function reverseEntry(User $actor, JournalEntry $entry): JournalEntry
    {
        if ($entry->is_reversed) {
            throw new InvalidArgumentException("Journal entry {$entry->entry_number} has already been reversed.");
        }

        if ($entry->source_type !== null) {
            throw new InvalidArgumentException(
                "Journal entry {$entry->entry_number} was auto-posted from a {$entry->source_type} document — ".
                'reverse it by correcting that document (e.g. a credit note, not this entry directly).'
            );
        }

        return DB::transaction(function () use ($actor, $entry) {
            $reversal = $this->entries->create([
                'tenant_id' => $actor->tenant_id,
                'entry_number' => $this->sequences->next($actor->tenant_id, 'journal_entry_number', 'JE'),
                'entry_date' => now()->toDateString(),
                'memo' => "Reversal of {$entry->entry_number}".($entry->memo ? " ({$entry->memo})" : ''),
                'source_type' => 'reversal',
                'source_id' => $entry->id,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($entry->lines as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $actor->tenant_id,
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            $this->entries->update($entry, ['is_reversed' => true, 'reversed_by_entry_id' => $reversal->id]);

            return $reversal->fresh('lines');
        });
    }
}
