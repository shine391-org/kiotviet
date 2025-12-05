<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Demo price lists with sample items for FE showcase.
 *
 * @agent-seeder: Demo price lists
 * @agent-pattern: Seed price_lists + price_list_items
 * @agent-reusable: MEDIUM
 */
class PriceListDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Demo price lists
        $lists = [
            [
                'id' => 1,
                'name' => 'VIP 20%',
                'type' => 'custom',
                'priority' => 5,
                'is_active' => 1,
                'start_date' => date('Y-m-d', strtotime('-10 days')),
                'end_date' => date('Y-m-d', strtotime('+30 days')),
                'apply_to_groups' => null,
                'formula' => null,
                'base_price_list_id' => null,
                'auto_update' => 0,
                'rounding_rule' => 'none',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'Group B -35%',
                'type' => 'custom',
                'priority' => 4,
                'is_active' => 1,
                'start_date' => date('Y-m-d', strtotime('-5 days')),
                'end_date' => date('Y-m-d', strtotime('+15 days')),
                'apply_to_groups' => json_encode([3]), // customer_group_id 3 (demo)
                'formula' => null,
                'base_price_list_id' => null,
                'auto_update' => 0,
                'rounding_rule' => 'none',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'Flash Sale 30%',
                'type' => 'custom',
                'priority' => 10,
                'is_active' => 1,
                'start_date' => date('Y-m-d', strtotime('-1 day')),
                'end_date' => date('Y-m-d', strtotime('+3 days')),
                'apply_to_groups' => null,
                'formula' => null,
                'base_price_list_id' => null,
                'auto_update' => 0,
                'rounding_rule' => 'none',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        $this->db->table('price_lists')->ignore(true)->insertBatch($lists);

        // Link items to existing demo products/variants
        $items = [
            // Price list 1: all main products
            ['price_list_id' => 1, 'product_id' => 501, 'variant_id' => null, 'price' => 6000000, 'discount_percent' => 20, 'discount_amount' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['price_list_id' => 1, 'product_id' => 502, 'variant_id' => null, 'price' => 1560000, 'discount_percent' => 20, 'discount_amount' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['price_list_id' => 1, 'product_id' => 503, 'variant_id' => null, 'price' => 1200000, 'discount_percent' => 20, 'discount_amount' => 0, 'created_at' => $now, 'updated_at' => $now],

            // Price list 2: group B special for product 503
            ['price_list_id' => 2, 'product_id' => 503, 'variant_id' => null, 'price' => 975000, 'discount_percent' => 35, 'discount_amount' => 0, 'created_at' => $now, 'updated_at' => $now],

            // Price list 3: flash sale on variant 7002 only
            ['price_list_id' => 3, 'product_id' => 501, 'variant_id' => 50102, 'price' => 5250000, 'discount_percent' => 30, 'discount_amount' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->db->table('price_list_items')->ignore(true)->insertBatch($items);
    }
}
