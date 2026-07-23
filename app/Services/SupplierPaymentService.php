<?php

namespace App\Services;

use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\User;
use App\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SupplierPaymentService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $payments,
        private readonly SequenceService $sequences,
        private readonly SupplierBillService $billService,
        private readonly PurchaseAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($actor, $data) {
            $payment = $this->payments->create([
                'tenant_id' => $actor->tenant_id,
                'payment_number' => $this->sequences->next($actor->tenant_id, 'supplier_payment_number', 'SPMT'),
                'supplier_id' => $data['supplier_id'],
                'amount' => $data['amount'],
                'allocated_amount' => 0,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            $this->accounting->postPaymentMade($actor, $payment, (float) $payment->amount);

            foreach ($data['allocations'] ?? [] as $allocation) {
                $this->allocate($actor, $payment, SupplierBill::findOrFail($allocation['supplier_bill_id']), (float) $allocation['amount']);
            }

            return $payment->fresh('allocations');
        });
    }

    public function payBill(User $actor, SupplierBill $bill, float $amount, array $meta = []): SupplierPayment
    {
        return $this->create($actor, array_merge([
            'supplier_id' => $bill->supplier_id,
            'amount' => $amount,
            'allocations' => [['supplier_bill_id' => $bill->id, 'amount' => $amount]],
        ], $meta));
    }

    public function allocate(User $actor, SupplierPayment $payment, SupplierBill $bill, float $amount): SupplierPaymentAllocation
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Allocation amount must be greater than zero.');
        }
        if ($bill->supplier_id !== $payment->supplier_id) {
            throw new InvalidArgumentException('This payment and bill belong to different suppliers.');
        }
        if ($amount > $payment->unallocatedAmount() + 0.001) {
            throw new InvalidArgumentException('Allocation exceeds this payment\'s unallocated amount.');
        }
        if ($amount > $bill->balanceDue() + 0.001) {
            throw new InvalidArgumentException('Allocation exceeds the bill\'s outstanding balance.');
        }

        return DB::transaction(function () use ($payment, $bill, $amount) {
            $allocation = SupplierPaymentAllocation::create([
                'tenant_id' => $payment->tenant_id,
                'supplier_payment_id' => $payment->id,
                'supplier_bill_id' => $bill->id,
                'amount' => $amount,
                'created_at' => now(),
            ]);

            $payment->update(['allocated_amount' => round(((float) $payment->allocated_amount) + $amount, 2)]);
            $bill->update(['paid_amount' => round(((float) $bill->paid_amount) + $amount, 2)]);
            $this->billService->recalculateStatus($bill->fresh());

            return $allocation;
        });
    }
}
