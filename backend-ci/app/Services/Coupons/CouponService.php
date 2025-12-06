<?php

namespace App\Services\Coupons;

use App\Repositories\Coupons\CouponRepository;
use InvalidArgumentException;
use RuntimeException;

class CouponService
{
    protected CouponRepository $repo;

    public function __construct(?CouponRepository $repo = null)
    {
        $this->repo = $repo ?? new CouponRepository();
    }

    public function list(array $filters): array
    {
        $rows = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);
        return [
            'success' => true,
            'data' => array_map([$this, 'transform'], $rows),
            'pagination' => [
                'page' => (int) ($filters['page'] ?? 1),
                'limit' => (int) ($filters['limit'] ?? 50),
                'total' => $total,
            ],
        ];
    }

    public function get(int $id): array
    {
        $coupon = $this->repo->findById($id);
        if (!$coupon) {
            throw new RuntimeException('Coupon not found');
        }
        return ['success' => true, 'data' => $this->transform($coupon)];
    }

    public function create(array $data): array
    {
        if (empty($data['code'])) {
            throw new InvalidArgumentException('Coupon code is required');
        }
        if ($this->repo->findByCode($data['code'])) {
            throw new InvalidArgumentException('Coupon code already exists');
        }

        $coupon = $this->repo->create([
            'code' => strtoupper($data['code']),
            'discount_type' => $data['discount_type'] ?? 'percent',
            'discount_value' => $data['discount_value'] ?? 0,
            'min_amount' => $data['min_amount'] ?? 0,
            'expiry_date' => $data['expiry_date'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? 0,
            'used_count' => 0,
            'status' => $data['status'] ?? 'active',
        ]);
        return ['success' => true, 'data' => $this->transform($coupon), 'message' => 'Coupon created'];
    }

    public function update(int $id, array $data): array
    {
        if (!$this->repo->findById($id)) {
            throw new RuntimeException('Coupon not found');
        }
        $allowed = ['code', 'discount_type', 'discount_value', 'min_amount', 'expiry_date', 'usage_limit', 'status'];
        $update = array_intersect_key($data, array_flip($allowed));
        if (isset($update['code'])) {
            $update['code'] = strtoupper($update['code']);
        }
        $coupon = $this->repo->update($id, $update);
        return ['success' => true, 'data' => $this->transform($coupon), 'message' => 'Coupon updated'];
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new RuntimeException('Coupon not found');
        }
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Coupon deleted'];
    }

    /** Validate and apply coupon for POS */
    public function apply(string $code, float $orderTotal, ?int $customerId = null): array
    {
        $coupon = $this->repo->findByCode(strtoupper($code));
        if (!$coupon) {
            throw new InvalidArgumentException('Coupon not found');
        }

        // Validate status
        if ($coupon['status'] !== 'active') {
            throw new InvalidArgumentException('Coupon is not active');
        }

        // Validate expiry
        if ($coupon['expiry_date'] && strtotime($coupon['expiry_date']) < time()) {
            throw new InvalidArgumentException('Coupon has expired');
        }

        // Validate usage limit
        if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
            throw new InvalidArgumentException('Coupon usage limit reached');
        }

        // Validate min amount
        if ($coupon['min_amount'] > 0 && $orderTotal < $coupon['min_amount']) {
            throw new InvalidArgumentException('Order total does not meet minimum amount of ' . number_format($coupon['min_amount']));
        }

        // Calculate discount
        $discountValue = (float) $coupon['discount_value'];
        $discount = 0;
        if ($coupon['discount_type'] === 'percent') {
            $discount = round($orderTotal * $discountValue / 100, 0);
        } else {
            $discount = min($discountValue, $orderTotal);
        }

        return [
            'success' => true,
            'data' => [
                'coupon_id' => (int) $coupon['id'],
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'discount_value' => $discountValue,
                'discount_amount' => $discount,
                'order_total' => $orderTotal,
                'final_total' => $orderTotal - $discount,
            ],
        ];
    }

    /** Record coupon usage after order completion */
    public function recordUsage(int $couponId, ?int $orderId, ?int $customerId): void
    {
        $this->repo->incrementUsage($couponId);
        $this->repo->recordUsage($couponId, $orderId, $customerId);
    }

    private function transform(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'discount_type' => $row['discount_type'],
            'discount_value' => (float) $row['discount_value'],
            'min_amount' => (float) $row['min_amount'],
            'expiry_date' => $row['expiry_date'],
            'usage_limit' => (int) $row['usage_limit'],
            'used_count' => (int) $row['used_count'],
            'status' => $row['status'],
            'is_expired' => $row['expiry_date'] && strtotime($row['expiry_date']) < time(),
            'remaining_uses' => $row['usage_limit'] > 0 ? max(0, $row['usage_limit'] - $row['used_count']) : null,
        ];
    }
}
