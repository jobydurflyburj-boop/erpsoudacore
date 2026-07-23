<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Repositories\Contracts\CreditNoteRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditNoteService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly CreditNoteRepositoryInterface $creditNotes,
        private readonly SequenceService $sequences,
        private readonly SalesInvoiceService $invoiceService,
        private readonly SalesAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): CreditNote
    {
        $invoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
        $totals = $this->calculateTotals($data['items']);

        if ($totals['total'] > $invoice->balanceDue() + 0.001) {
            throw new InvalidArgumentException('Credit note total exceeds the invoice\'s outstanding balance.');
        }

        return DB::transaction(function () use ($actor, $data, $invoice, $totals) {
            $creditNote = $this->creditNotes->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'credit_note_number', 'CN'),
                'customer_id' => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
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
                CreditNoteItem::create([
                    'tenant_id' => $creditNote->tenant_id,
                    'credit_note_id' => $creditNote->id,
                    'product_id' => $line['product_id'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'line_total' => $line['line_total'],
                ]);
            }

            return $creditNote;
        });
    }

    /** Issuing is the real financial event: posts the reversing journal entry and reduces the invoice's balance due. */
    public function issue(User $actor, CreditNote $creditNote): CreditNote
    {
        if ($creditNote->status !== 'draft') {
            throw new InvalidArgumentException("Credit note {$creditNote->document_number} has already been issued.");
        }

        $invoice = $creditNote->salesInvoice;

        if ($creditNote->total > $invoice->balanceDue() + 0.001) {
            throw new InvalidArgumentException('This credit note would exceed the invoice\'s current outstanding balance.');
        }

        return DB::transaction(function () use ($actor, $creditNote, $invoice) {
            $creditNote = $this->creditNotes->update($creditNote, ['status' => 'issued', 'updated_by_user_id' => $actor->id]);

            $invoice->update(['credited_amount' => round(((float) $invoice->credited_amount) + (float) $creditNote->total, 2)]);
            $this->invoiceService->recalculateStatus($invoice->fresh());

            $this->accounting->postCreditNoteIssued($actor, $creditNote);

            return $creditNote;
        });
    }
}
