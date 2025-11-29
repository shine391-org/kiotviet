<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate payment entry operations.
 *
 * @agent-validator: Payment entry
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PaymentEntryValidator
{
    public function validateCreate(array $input): array
    {
        $orderId = isset($input['order_id']) ? (int) $input['order_id'] : 0;
        if ($orderId <= 0) {
            throw new InvalidArgumentException('order_id is required');
        }
        $method = strtoupper(trim((string) ($input['payment_method'] ?? '')));
        if ($method === '') {
            throw new InvalidArgumentException('payment_method is required');
        }
        $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
        if ($amount <= 0) {
            throw new InvalidArgumentException('amount must be > 0');
        }
        return [
            'order_id' => $orderId,
            'payment_method' => $method,
            'amount' => $amount,
            'reference' => isset($input['reference']) ? trim((string) $input['reference']) : null,
            'status' => $input['status'] ?? 'posted',
        ];
    }
}
