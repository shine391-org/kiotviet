<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate loyalty program inputs.
 *
 * @agent-validator: Loyalty program
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class LoyaltyProgramValidator
{
    public function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $earn = isset($input['earn_rate']) ? (float) $input['earn_rate'] : 0;
        $redeem = isset($input['redeem_rate']) ? (float) $input['redeem_rate'] : 0;
        if ($earn < 0 || $redeem <= 0) {
            throw new InvalidArgumentException('earn_rate must be >=0 and redeem_rate > 0');
        }
        $expiry = isset($input['expiry_days']) ? (int) $input['expiry_days'] : 365;
        if ($expiry < 0) {
            throw new InvalidArgumentException('expiry_days must be >= 0');
        }

        return [
            'name' => $name,
            'customer_group_id' => isset($input['customer_group_id']) ? (int) $input['customer_group_id'] : null,
            'earn_rate' => $earn,
            'redeem_rate' => $redeem,
            'expiry_days' => $expiry,
            'status' => $input['status'] ?? 'active',
        ];
    }
}
