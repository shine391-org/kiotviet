<?php

namespace App\Repositories\Coupons;

use App\Models\CouponModel;
use App\Models\CouponUsageModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Coupons
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class CouponRepository
{
    protected BaseConnection $db;

    public function __construct(?CouponModel $coupons = null, ?CouponUsageModel $usages = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->db->table('coupons')->where('code', strtoupper($code))->get()->getRowArray();
        return $row ? $this->hydrate($row) : null;
    }

    public function incrementUsage(int $couponId): void
    {
        $this->db->table('coupons')->where('id', $couponId)->set('used_count', 'used_count + 1', false)->update();
    }

    public function recordUsage(int $couponId, ?int $orderId, ?int $customerId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('coupon_usages')->insert([
            'coupon_id' => $couponId,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'used_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['discount_value'] = isset($row['discount_value']) ? (float) $row['discount_value'] : 0.0;
        $row['min_amount'] = isset($row['min_amount']) ? (float) $row['min_amount'] : 0.0;
        $row['usage_limit'] = isset($row['usage_limit']) ? (int) $row['usage_limit'] : 0;
        $row['used_count'] = isset($row['used_count']) ? (int) $row['used_count'] : 0;
        return $row;
    }
}
