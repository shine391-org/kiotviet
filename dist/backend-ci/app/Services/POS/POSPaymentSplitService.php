<?php

namespace App\Services\POS;

use InvalidArgumentException;

/**
 * @agent-service: POS payment split validation
 * @agent-pattern: Validation service
 * @agent-reusable: HIGH
 */
class POSPaymentSplitService
{
    /**
     * Validate and normalize multi-payment payload for POS checkout.
     *
     * @agent-use: POS checkout
     * @agent-pattern: Sum check + allow-list
     */
    public function validate(array $payments, array $allowedMethods, float $orderTotal): array
    {
        if (empty($payments)) {
            throw new InvalidArgumentException('payments is required for POS checkout');
        }
        $allowMap = [];
        foreach ($allowedMethods as $method) {
            $code = strtoupper(is_array($method) ? ($method['payment_method'] ?? ($method['code'] ?? '')) : (string) $method);
            if ($code !== '') {
                $allowMap[$code] = true;
            }
        }
        if (empty($allowMap)) {
            throw new InvalidArgumentException('No payment methods configured for POS profile');
        }

        $normalized = [];
        foreach ($payments as $payment) {
            $code = strtoupper(trim((string) ($payment['payment_method'] ?? '')));
            $amount = isset($payment['amount']) ? (float) $payment['amount'] : 0;
            if ($code === '' || $amount <= 0) {
                throw new InvalidArgumentException('payment_method and amount are required for each payment');
            }
            if (! isset($allowMap[$code])) {
                throw new InvalidArgumentException("Payment method {$code} is not allowed for this profile");
            }
            $normalized[] = [
                'payment_method' => $code,
                'amount' => $amount,
            ];
        }

        $total = array_sum(array_column($normalized, 'amount'));
        if (abs($total - $orderTotal) > 0.01) {
            throw new InvalidArgumentException('Total payment amount must equal order total');
        }

        return $normalized;
    }
}
