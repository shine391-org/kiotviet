<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Create zones
        $zones = [
            [
                'name' => 'Nội thành Hà Nội',
                'province_ids' => json_encode([1]), // Hanoi
                'district_ids' => json_encode([1, 2, 3, 4, 5]), // Inner districts
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Nội thành HCM',
                'province_ids' => json_encode([2]), // HCM
                'district_ids' => json_encode([21, 22, 23, 24, 25]), // Inner districts
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Miền Bắc',
                'province_ids' => json_encode([1, 4, 5, 6, 7, 8, 9, 10]),
                'district_ids' => json_encode([]),
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Miền Trung',
                'province_ids' => json_encode([3, 11, 12, 13, 14, 15, 16]),
                'district_ids' => json_encode([]),
                'is_active' => 1,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Miền Nam',
                'province_ids' => json_encode([2, 17, 18, 19, 20]),
                'district_ids' => json_encode([]),
                'is_active' => 1,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Toàn quốc (mặc định)',
                'province_ids' => json_encode([]),
                'district_ids' => json_encode([]),
                'is_active' => 1,
                'sort_order' => 99,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('shipping_zones')->insertBatch($zones);
        echo "ShippingZoneSeeder: Seeded " . count($zones) . " shipping zones.\n";

        // Create rates
        $rates = [
            // Nội thành HN/HCM - free for orders > 500k
            ['zone_id' => 1, 'base_fee' => 20000, 'per_kg_fee' => 0, 'free_shipping_threshold' => 500000],
            ['zone_id' => 2, 'base_fee' => 20000, 'per_kg_fee' => 0, 'free_shipping_threshold' => 500000],
            // Regional
            ['zone_id' => 3, 'base_fee' => 30000, 'per_kg_fee' => 5000, 'free_shipping_threshold' => 1000000],
            ['zone_id' => 4, 'base_fee' => 35000, 'per_kg_fee' => 6000, 'free_shipping_threshold' => 1000000],
            ['zone_id' => 5, 'base_fee' => 30000, 'per_kg_fee' => 5000, 'free_shipping_threshold' => 1000000],
            // Default
            ['zone_id' => 6, 'base_fee' => 40000, 'per_kg_fee' => 7000, 'free_shipping_threshold' => null],
        ];

        foreach ($rates as &$rate) {
            $rate['min_weight'] = 0;
            $rate['max_weight'] = 999999;
            $rate['min_value'] = 0;
            $rate['max_value'] = 999999999;
            $rate['is_active'] = 1;
            $rate['created_at'] = $now;
            $rate['updated_at'] = $now;
        }

        $this->db->table('shipping_rates')->insertBatch($rates);
        echo "ShippingZoneSeeder: Seeded " . count($rates) . " shipping rates.\n";
    }
}
