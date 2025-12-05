<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate credit control inputs.
 *
 * @agent-validator: CreditControl
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CreditControlValidator
{
    public function validateLimit(array $input): array
    {
        $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : 0;
        if ($customerId <= 0) {
            throw new InvalidArgumentException('customer_id is required');
        }
        $limit = isset($input['limit_amount']) ? (float) $input['limit_amount'] : 0;
        if ($limit < 0) {
            throw new InvalidArgumentException('limit_amount must be >= 0');
        }
        return [
            'customer_id' => $customerId,
            'limit_amount' => $limit,
            'on_hold' => ! empty($input['on_hold']),
        ];
    }

    public function validateCheck(array $input): array
    {
        $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : 0;
        if ($customerId <= 0) {
            throw new InvalidArgumentException('customer_id is required');
        }
        $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
        if ($amount < 0) {
            throw new InvalidArgumentException('amount must be >= 0');
        }
        return [
            'customer_id' => $customerId,
            'amount' => $amount,
            'allow_override' => ! empty($input['allow_override']),
        ];
    }
}
