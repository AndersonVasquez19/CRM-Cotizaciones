<?php

use App\Actions\Quotes\CalculateQuoteTotalsAction;

it('calculates line totals, subtotal, discount, igv, total and gross margin accurately', function () {
    $action = new CalculateQuoteTotalsAction();

    $result = $action->execute([
        [
            'section_name' => '1. Equipamiento',
            'description' => 'Cámara IP Domo 4MP',
            'unit' => 'UND',
            'quantity' => 4,
            'unit_cost' => 200.00,
            'unit_price' => 350.00,
            'discount_percent' => 10, // 4 * 350 * 0.9 = 1260.00
        ],
        [
            'section_name' => '2. Servicios',
            'description' => 'Instalación Cámara IP',
            'unit' => 'UND',
            'quantity' => 4,
            'unit_cost' => 50.00,
            'unit_price' => 100.00,
            'discount_percent' => 0, // 400.00
        ],
    ], 60.00);

    expect($result['items'][0]['line_total'])->toBe(1260.0)
        ->and($result['items'][1]['line_total'])->toBe(400.0)
        ->and($result['subtotal'])->toBe(1660.0)
        ->and($result['discount_amount'])->toBe(60.0)
        // Net subtotal = 1600.00, IGV 18% = 288.00, Total = 1888.00
        ->and($result['tax_amount'])->toBe(288.0)
        ->and($result['total'])->toBe(1888.0)
        // Total cost = 4*200 + 4*50 = 1000.00, Margin = 1600 - 1000 = 600.00 (37.5%)
        ->and($result['total_cost'])->toBe(1000.0)
        ->and($result['gross_margin_amount'])->toBe(600.0)
        ->and($result['gross_margin_percent'])->toBe(37.5);
});

it('caps global discount at subtotal so total never becomes negative', function () {
    $action = new CalculateQuoteTotalsAction();

    $result = $action->execute([
        [
            'description' => 'Item prueba',
            'unit' => 'UND',
            'quantity' => 1,
            'unit_cost' => 50,
            'unit_price' => 100,
        ],
    ], 500.00);

    expect($result['subtotal'])->toBe(100.0)
        ->and($result['discount_amount'])->toBe(100.0)
        ->and($result['tax_amount'])->toBe(0.0)
        ->and($result['total'])->toBe(0.0);
});
