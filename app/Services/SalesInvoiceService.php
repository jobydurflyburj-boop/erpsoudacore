<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesInvoiceService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoices,
        private readonly SequenceService $sequences,
        private readonly SalesAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): SalesInvoice
    {
        return DB::transaction(function () use ($actor, $data) {
            $totals = $this->calculateTotals($data['items']);

            $invoice = $this->invoices->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'invoice_number', 'INV'),
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['total'],
                'paid_amount' => 0,
                'credited_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceItems($invoice, $totals['lines']);

            return $invoice;
        });
    }

    public function createFromSalesOrder(User $actor, SalesOrder $order): SalesInvoice
    {
        if ($order->status !== 'confirmed') {
            throw new InvalidArgumentException('Only a confirmed sales order can be invoiced.');
        }

        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'vat_rate' => (float) $item->vat_rate,
        ])->all();

        return $this->create($actor, [
            'customer_id' => $order->customer_id,
            'sales_order_id' => $order->id,
            'items' => $items,
            'notes' => $order->notes,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function update(User $actor, SalesInvoice $invoice, array $data): SalesInvoice
    {
        return DB::transaction(function () use ($actor, $invoice, $data) {
            $updates = ['updated_by_user_id' => $actor->id];

            if (array_key_exists('items', $data)) {
                $totals = $this->calculateTotals($data['items']);
                $updates = array_merge($updates, [
                    'subtotal' => $totals['subtotal'], 'vat_amount' => $totals['vat_amount'], 'total' => $totals['total'],
                ]);
                $invoice->items()->delete();
                $this->replaceItems($invoice, $totals['lines']);
            }

            foreach (['customer_id', 'document_date', 'due_date', 'notes'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            return $this->invoices->update($invoice, $updates)->fresh('items');
        });
    }

    /**
     * Issuing an invoice is now a purely FINANCIAL event — it posts a
     * real journal entry (Dr AR, Cr Revenue, Cr VAT Payable). It no
     * longer moves stock: that's Delivery Note's job
     * (DeliveryNoteService::deliver()), a deliberate redesign this
     * sprint so the warehouse event and the financial event are
     * tracked separately, the way a real ERP models them — an invoice
     * can be issued before, after, or without a delivery (e.g. a
     * services invoice), and a delivery can happen without an invoice
     * yet existing.
     */
    public function issue(User $actor, SalesInvoice $invoice): SalesInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException("Invoice {$invoice->document_number} has already been issued.");
        }

        return DB::transaction(function () use ($actor, $invoice) {
            $invoice = $this->invoices->update($invoice, ['status' => 'issued', 'updated_by_user_id' => $actor->id]);
            $this->accounting->postInvoiceIssued($actor, $invoice);

            return $invoice;
        });
    }

    /**
     * Recomputes status from the real paid_amount/credited_amount
     * state — called by CustomerPaymentService and CreditNoteService
     * after they change either figure, so status never drifts out of
     * sync with the numbers that actually determine it.
     */
    public function recalculateStatus(SalesInvoice $invoice): SalesInvoice
    {
        $balance = $invoice->balanceDue();
        $status = $invoice->status;

        if ($status !== 'cancelled') {
            if ($balance <= 0.0) {
                $status = 'paid';
            } elseif ((float) $invoice->paid_amount > 0) {
                $status = 'partial';
            } elseif ($invoice->status === 'paid' || $invoice->status === 'partial') {
                $status = 'issued';
            }
        }

        return $status === $invoice->status ? $invoice : $this->invoices->update($invoice, ['status' => $status]);
    }

    private function replaceItems(SalesInvoice $invoice, array $lines): void
    {
        foreach ($lines as $line) {
            SalesInvoiceItem::create([
                'tenant_id' => $invoice->tenant_id,
                'sales_invoice_id' => $invoice->id,
                'product_id' => $line['product_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'vat_rate' => $line['vat_rate'],
                'line_total' => $line['line_total'],
            ]);
        }
    }
}
