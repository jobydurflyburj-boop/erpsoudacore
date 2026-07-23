<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use App\Repositories\Contracts\SupplierBillRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SupplierBillService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly SupplierBillRepositoryInterface $bills,
        private readonly SequenceService $sequences,
        private readonly PurchaseAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): SupplierBill
    {
        return DB::transaction(function () use ($actor, $data) {
            $totals = $this->calculateTotals($data['items'], 'unit_cost');

            $bill = $this->bills->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'supplier_bill_number', 'BILL'),
                'supplier_id' => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
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

            foreach ($totals['lines'] as $line) {
                SupplierBillItem::create([
                    'tenant_id' => $bill->tenant_id,
                    'supplier_bill_id' => $bill->id,
                    'product_id' => $line['product_id'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'vat_rate' => $line['vat_rate'],
                    'line_total' => $line['line_total'],
                ]);
            }

            return $bill;
        });
    }

    /** Bills straight against what was actually received — copies quantities and costs from the Goods Receipt, not the original PO (which may differ from what actually arrived). */
    public function createFromGoodsReceipt(User $actor, GoodsReceipt $receipt): SupplierBill
    {
        if ($receipt->status !== 'received') {
            throw new InvalidArgumentException('Only a received goods receipt can be billed.');
        }
        if (! $receipt->supplier_id) {
            throw new InvalidArgumentException('This goods receipt has no supplier to bill.');
        }

        $items = $receipt->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
        ])->all();

        return $this->create($actor, [
            'supplier_id' => $receipt->supplier_id,
            'purchase_order_id' => $receipt->purchase_order_id,
            'goods_receipt_id' => $receipt->id,
            'items' => $items,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function update(User $actor, SupplierBill $bill, array $data): SupplierBill
    {
        return DB::transaction(function () use ($actor, $bill, $data) {
            $updates = ['updated_by_user_id' => $actor->id];

            if (array_key_exists('items', $data)) {
                $totals = $this->calculateTotals($data['items'], 'unit_cost');
                $updates = array_merge($updates, [
                    'subtotal' => $totals['subtotal'], 'vat_amount' => $totals['vat_amount'], 'total' => $totals['total'],
                ]);
                $bill->items()->delete();
                foreach ($totals['lines'] as $line) {
                    SupplierBillItem::create([
                        'tenant_id' => $bill->tenant_id, 'supplier_bill_id' => $bill->id, 'product_id' => $line['product_id'],
                        'description' => $line['description'] ?? null, 'quantity' => $line['quantity'],
                        'unit_cost' => $line['unit_cost'], 'vat_rate' => $line['vat_rate'], 'line_total' => $line['line_total'],
                    ]);
                }
            }

            foreach (['supplier_id', 'document_date', 'due_date', 'notes'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            return $this->bills->update($bill, $updates)->fresh('items');
        });
    }

    /** Approving is the real financial event: posts to Accounts Payable. */
    public function approve(User $actor, SupplierBill $bill): SupplierBill
    {
        if ($bill->status !== 'draft') {
            throw new InvalidArgumentException("Supplier bill {$bill->document_number} has already been approved.");
        }

        return DB::transaction(function () use ($actor, $bill) {
            $bill = $this->bills->update($bill, ['status' => 'approved', 'updated_by_user_id' => $actor->id]);
            $this->accounting->postBillApproved($actor, $bill);

            return $bill;
        });
    }

    /** Recomputes status from the real paid/credited state, mirroring SalesInvoiceService::recalculateStatus(). */
    public function recalculateStatus(SupplierBill $bill): SupplierBill
    {
        $balance = $bill->balanceDue();
        $status = $bill->status;

        if (! in_array($status, ['cancelled', 'draft'], true)) {
            if ($balance <= 0.0) {
                $status = 'paid';
            } elseif ((float) $bill->paid_amount > 0) {
                $status = 'partial';
            } else {
                $status = 'approved';
            }
        }

        return $status === $bill->status ? $bill : $this->bills->update($bill, ['status' => $status]);
    }
}
