<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate coupons.
 *
 * @agent-validator: Coupon
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CouponValidator
{
    public function validateCreate(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($code === '') {
            throw new InvalidArgumentException('code is required');
        }
        $discountType = strtoupper($input['discount_type'] ?? 'PERCENT');
        if (! in_array($discountType, ['PERCENT', 'FIXED'], true)) {
            throw new InvalidArgumentException('discount_type must be percent or fixed');
        }
        $value = isset($input['discount_value']) ? (float) $input['discount_value'] : 0;
        if ($value <= 0) {
            throw new InvalidArgumentException('discount_value must be > 0');
        }
        return [
            'code' => $code,
            'discount_type' => strtolower($discountType),
            'discount_value' => $value,
            'min_amount' => isset($input['min_amount']) ? (float) $input['min_amount'] : 0.0,
            'expiry_date' => $input['expiry_date'] ?? null,
            'usage_limit' => isset($input['usage_limit']) ? (int) $input['usage_limit'] : 0,
            'status' => $input['status'] ?? 'active',
        ];
    }

    public function validateApply(string $code, float $amount): string
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('amount must be > 0');
        }
        $trimmed = strtoupper(trim($code));
        if ($trimmed === '') {
            throw new InvalidArgumentException('coupon code is required');
        }
        return $trimmed;
    }
}
