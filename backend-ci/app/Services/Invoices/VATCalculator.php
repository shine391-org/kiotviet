<?php

namespace App\Services\Invoices;

/**
 * VAT calculation helper.
 *
 * @agent-service: VAT calculator
 * @agent-pattern: Pure function
 * @agent-reusable: HIGH
 */
class VATCalculator
{
    public function calculate(float $subtotal, float $vatRate): array
    {
        $vatAmount = round($subtotal * $vatRate, 2);
        $total = round($subtotal + $vatAmount, 2);
        return [
            'vat_amount' => $vatAmount,
            'total' => $total,
        ];
    }
}
