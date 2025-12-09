<?php

namespace Tests\Support\Database;

/**
 * Reset Coupon-related tables for tests.
 *
 * @agent-test-support: Coupon schema reset
 * @agent-pattern: Truncate with FK disable
 */
trait CouponSchemaTrait
{
    protected function resetCouponSchema(): void
    {
        $tables = [
            'coupon_usages',
            'coupons',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function createCoupon(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'code' => 'COUPON-' . strtoupper(uniqid()),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_amount' => 0,
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
            'usage_limit' => 0,
            'used_count' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('coupons')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }
}
