<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\PaymentAllocation;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Repositories\Contracts\CustomerPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The canonical payment path, replacing the MVP's direct paid_amount
 * bump. A payment is recorded once and can be allocated across one or
 * more invoices — a real accounts-receivable pattern, not a
 * one-payment-one-invoice simplification.
 */
class CustomerPaymentService
{
    public function __construct(
        private readonly CustomerPaymentRepositoryInterface $payments,
        private readonly SequenceService $sequences,
        private readonly SalesInvoiceService $invoiceService,
        private readonly SalesAccountingIntegrationService $accounting,
    ) {}

    /**
     * @param array $allocations optional [['sales_invoice_id'=>, 'amount'=>], ...] — if omitted, the payment is recorded unallocated (a customer overpayment / advance) and can be allocated later via allocate().
     */
    public function create(User $actor, array $data): CustomerPayment
    {
        return DB::transaction(function () use ($actor, $data) {
            $payment = $this->payments->create([
                'tenant_id' => $actor->tenant_id,
                'payment_number' => $this->sequences->next($actor->tenant_id, 'payment_number', 'PMT'),
                'customer_id' => $data['customer_id'],
                'amount' => $data['amount'],
                'allocated_amount' => 0,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            $this->accounting->postPaymentReceived($actor, $payment, (float) $payment->amount);

            foreach ($data['allocations'] ?? [] as $allocation) {
                $this->allocate($actor, $payment, SalesInvoice::findOrFail($allocation['sales_invoice_id']), (float) $allocation['amount']);
            }

            return $payment->fresh('allocations');
        });
    }

    /** Convenience path for the common case: pay exactly one invoice, fully or partially, in one call. */
    public function payInvoice(User $actor, SalesInvoice $invoice, float $amount, array $meta = []): CustomerPayment
    {
        return $this->create($actor, array_merge([
            'customer_id' => $invoice->customer_id,
            'amount' => $amount,
            'allocations' => [['sales_invoice_id' => $invoice->id, 'amount' => $amount]],
        ], $meta));
    }

    public function allocate(User $actor, CustomerPayment $payment, SalesInvoice $invoice, float $amount): PaymentAllocation
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Allocation amount must be greater than zero.');
        }
        if ($invoice->customer_id !== $payment->customer_id) {
            throw new InvalidArgumentException('This payment and invoice belong to different customers.');
        }
        if ($amount > $payment->unallocatedAmount() + 0.001) {
            throw new InvalidArgumentException('Allocation exceeds this payment\'s unallocated amount.');
        }
        if ($amount > $invoice->balanceDue() + 0.001) {
            throw new InvalidArgumentException('Allocation exceeds the invoice\'s outstanding balance.');
        }

        return DB::transaction(function () use ($payment, $invoice, $amount) {
            $allocation = PaymentAllocation::create([
                'tenant_id' => $payment->tenant_id,
                'customer_payment_id' => $payment->id,
                'sales_invoice_id' => $invoice->id,
                'amount' => $amount,
                'created_at' => now(),
            ]);

            $payment->update(['allocated_amount' => round(((float) $payment->allocated_amount) + $amount, 2)]);
            $invoice->update(['paid_amount' => round(((float) $invoice->paid_amount) + $amount, 2)]);
            $this->invoiceService->recalculateStatus($invoice->fresh());

            return $allocation;
        });
    }
}
