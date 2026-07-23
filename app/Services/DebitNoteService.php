<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\DebitNoteItem;
use App\Models\SupplierBill;
use App\Models\User;
use App\Repositories\Contracts\DebitNoteRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DebitNoteService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly DebitNoteRepositoryInterface $debitNotes,
        private readonly SequenceService $sequences,
        private readonly SupplierBillService $billService,
        private readonly PurchaseAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): DebitNote
    {
        $bill = SupplierBill::findOrFail($data['supplier_bill_id']);
        $totals = $this->calculateTotals($data['items']);

        if ($totals['total'] > $bill->balanceDue() + 0.001) {
            throw new InvalidArgumentException('Debit note total exceeds the bill\'s outstanding balance.');
        }

        return DB::transaction(function () use ($actor, $data, $bill, $totals) {
            $debitNote = $this->debitNotes->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'debit_note_number', 'DBN'),
                'supplier_id' => $bill->supplier_id,
                'supplier_bill_id' => $bill->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['total'],
                'reason' => $data['reason'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($totals['lines'] as $line) {
                DebitNoteItem::create([
                    'tenant_id' => $debitNote->tenant_id, 'debit_note_id' => $debitNote->id, 'product_id' => $line['product_id'],
                    'description' => $line['description'] ?? null, 'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'], 'vat_rate' => $line['vat_rate'], 'line_total' => $line['line_total'],
                ]);
            }

            return $debitNote;
        });
    }

    public function issue(User $actor, DebitNote $debitNote): DebitNote
    {
        if ($debitNote->status !== 'draft') {
            throw new InvalidArgumentException("Debit note {$debitNote->document_number} has already been issued.");
        }

        $bill = $debitNote->supplierBill;

        if ($debitNote->total > $bill->balanceDue() + 0.001) {
            throw new InvalidArgumentException('This debit note would exceed the bill\'s current outstanding balance.');
        }

        return DB::transaction(function () use ($actor, $debitNote, $bill) {
            $debitNote = $this->debitNotes->update($debitNote, ['status' => 'issued', 'updated_by_user_id' => $actor->id]);

            $bill->update(['credited_amount' => round(((float) $bill->credited_amount) + (float) $debitNote->total, 2)]);
            $this->billService->recalculateStatus($bill->fresh());

            $this->accounting->postDebitNoteIssued($actor, $debitNote);

            return $debitNote;
        });
    }
}
