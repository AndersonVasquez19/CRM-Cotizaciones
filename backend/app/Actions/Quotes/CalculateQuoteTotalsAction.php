<?php

namespace App\Actions\Quotes;

class CalculateQuoteTotalsAction
{
    public const IGV_RATE = 0.18;

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal: float,
     *     discount_amount: float,
     *     tax_amount: float,
     *     total: float,
     *     total_cost: float,
     *     gross_margin_amount: float,
     *     gross_margin_percent: float
     * }
     */
    public function execute(array $items, float $discountAmount = 0.0): array
    {
        $calculatedItems = [];
        $subtotal = 0.0;
        $totalCost = 0.0;

        foreach ($items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);

            $grossLine = $quantity * $unitPrice;
            $lineTotal = round($grossLine * (1 - ($discountPercent / 100)), 2);
            $lineCost = round($quantity * $unitCost, 2);

            $subtotal += $lineTotal;
            $totalCost += $lineCost;

            $calculatedItems[] = [
                'id' => $item['id'] ?? null,
                'catalog_item_id' => $item['catalog_item_id'] ?? null,
                'section_name' => $item['section_name'] ?? 'General',
                'description' => $item['description'],
                'unit' => $item['unit'] ?? 'UND',
                'quantity' => round($quantity, 2),
                'unit_cost' => round($unitCost, 2),
                'unit_price' => round($unitPrice, 2),
                'discount_percent' => round($discountPercent, 2),
                'line_total' => $lineTotal,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ];
        }

        $subtotal = round($subtotal, 2);
        $totalCost = round($totalCost, 2);
        $appliedDiscount = round(min(max($discountAmount, 0.0), $subtotal), 2);
        $netSubtotal = round($subtotal - $appliedDiscount, 2);
        $taxAmount = round($netSubtotal * self::IGV_RATE, 2);
        $total = round($netSubtotal + $taxAmount, 2);

        $grossMarginAmount = round($netSubtotal - $totalCost, 2);
        $grossMarginPercent = $netSubtotal > 0
            ? round(($grossMarginAmount / $netSubtotal) * 100, 2)
            : 0.0;

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'discount_amount' => $appliedDiscount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'total_cost' => $totalCost,
            'gross_margin_amount' => $grossMarginAmount,
            'gross_margin_percent' => $grossMarginPercent,
        ];
    }
}
