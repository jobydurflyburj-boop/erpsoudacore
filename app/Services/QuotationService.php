<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use App\Services\Concerns\CalculatesDocumentTotals;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    use CalculatesDocumentTotals;

    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly SequenceService $sequences,
    ) {}

    public function create(User $actor, array $data): Quotation
    {
        return DB::transaction(function () use ($actor, $data) {
            $totals = $this->calculateTotals($data['items']);

            $quotation = $this->quotations->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'quotation_number', 'QT'),
                'customer_id' => $data['customer_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'subtotal' => $totals['subtotal'],
                'vat_amount' => $totals['vat_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->replaceItems($quotation, $totals['lines']);

            return $quotation;
        });
    }

    public function update(User $actor, Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($actor, $quotation, $data) {
            $updates = ['updated_by_user_id' => $actor->id];

            if (array_key_exists('items', $data)) {
                $totals = $this->calculateTotals($data['items']);
                $updates = array_merge($updates, [
                    'subtotal' => $totals['subtotal'], 'vat_amount' => $totals['vat_amount'], 'total' => $totals['total'],
                ]);
                $quotation->items()->delete();
                $this->replaceItems($quotation, $totals['lines']);
            }

            foreach (['customer_id', 'document_date', 'notes', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            return $this->quotations->update($quotation, $updates)->fresh('items');
        });
    }

    private function replaceItems(Quotation $quotation, array $lines): void
    {
        foreach ($lines as $line) {
            QuotationItem::create([
                'tenant_id' => $quotation->tenant_id,
                'quotation_id' => $quotation->id,
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
