<?php

namespace App\Services\Concerns;

/**
 * Shared line-item total/VAT calculation for every sales/purchase
 * document (Quotation, Sales Order, Sales Invoice, Purchase Order).
 * Deliberately real math, not a placeholder — VAT is computed per line
 * at that line's own vat_rate (products can have different rates), not
 * a single flat rate assumption.
 */
trait CalculatesDocumentTotals
{
    /**
     * @param array $items each: ['quantity'=>, 'unit_price'=> or 'unit_cost'=>, 'vat_rate'=> (optional, default 15)]
     * @return array{lines: array, subtotal: float, vat_amount: float, total: float}
     */
    protected function calculateTotals(array $items, string $priceKey = 'unit_price'): array
    {
        $subtotal = 0.0;
        $vatAmount = 0.0;
        $lines = [];

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $price = (float) $item[$priceKey];
            $vatRate = (float) ($item['vat_rate'] ?? 15.00);

            $lineSubtotal = round($quantity * $price, 2);
            $lineVat = round($lineSubtotal * $vatRate / 100, 2);

            $subtotal += $lineSubtotal;
            $vatAmount += $lineVat;

            $lines[] = array_merge($item, [
                'vat_rate' => $vatRate,
                'line_total' => $lineSubtotal,
            ]);
        }

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'vat_amount' => round($vatAmount, 2),
            'total' => round($subtotal + $vatAmount, 2),
        ];
    }
}
