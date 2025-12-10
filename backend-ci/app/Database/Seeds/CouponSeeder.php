<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $nextMonth = date('Y-m-d', strtotime('+1 month'));

        $coupons = [
            [
                'code' => 'WELCOME10',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_amount' => 100000,
                'expiry_date' => $nextMonth,
                'usage_limit' => 100,
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SALE50K',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_amount' => 500000,
                'expiry_date' => $nextMonth,
                'usage_limit' => 50,
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'VIP20',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'min_amount' => 1000000,
                'expiry_date' => $nextMonth,
                'usage_limit' => 20,
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FREESHIP',
                'discount_type' => 'fixed',
                'discount_value' => 30000,
                'min_amount' => 200000,
                'expiry_date' => $nextMonth,
                'usage_limit' => 0, // unlimited
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Get existing coupon codes to avoid duplicates
        $existingCodes = $this->db->table('coupons')
            ->whereIn('code', array_column($coupons, 'code'))
            ->get()
            ->getResultArray();
        $existingCodesMap = array_column($existingCodes, 'code');

        // Filter out coupons that already exist
        $newCoupons = array_filter($coupons, fn($c) => !in_array($c['code'], $existingCodesMap));

        if (!empty($newCoupons)) {
            $this->db->table('coupons')->insertBatch(array_values($newCoupons));
            echo "CouponSeeder: Seeded " . count($newCoupons) . " new coupons.\n";
        } else {
            echo "CouponSeeder: All coupons already exist, skipped.\n";
        }
    }
}
