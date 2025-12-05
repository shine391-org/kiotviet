<?php

namespace App\Services\Coupons;

use App\Repositories\Coupons\CouponRepository;
use App\Validators\CouponValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Coupon validation + usage
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class CouponService
{
    protected CouponRepository $repo;
    protected CouponValidator $validator;

    public function __construct(?CouponRepository $repo = null, ?CouponValidator $validator = null)
    {
        $this->repo = $repo ?? new CouponRepository();
        $this->validator = $validator ?? new CouponValidator();
    }

    /**
     * Validate and calculate discount for amount.
     *
     * @agent-use: POS checkout
     */
    public function apply(string $code, float $amount): array
    {
        $normalizedCode = $this->validator->validateApply($code, $amount);
        $coupon = $this->repo->findByCode($normalizedCode);
        if (! $coupon) {
            throw new RuntimeException('Coupon not found');
        }
        if (($coupon['status'] ?? '') !== 'active') {
            throw new RuntimeException('Coupon inactive');
        }
        if (! empty($coupon['expiry_date']) && $coupon['expiry_date'] < date('Y-m-d')) {
            throw new RuntimeException('Coupon expired');
        }
        if (! empty($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
            throw new RuntimeException('Coupon usage limit reached');
        }
        if (! empty($coupon['min_amount']) && $amount < $coupon['min_amount']) {
            throw new RuntimeException('Order does not meet coupon minimum amount');
        }

        $discount = 0.0;
        if (($coupon['discount_type'] ?? 'percent') === 'percent') {
            $discount = round($amount * ($coupon['discount_value'] / 100), 2);
        } else {
            $discount = (float) $coupon['discount_value'];
        }
        $discount = min($discount, $amount);

        return [
            'success' => true,
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    /**
     * Mark coupon usage after successful order.
     */
    public function markUsed(array $coupon, ?int $orderId, ?int $customerId): void
    {
        if (empty($coupon['id'])) {
            throw new InvalidArgumentException('coupon id missing');
        }
        $this->repo->incrementUsage((int) $coupon['id']);
        $this->repo->recordUsage((int) $coupon['id'], $orderId, $customerId);
    }
}
