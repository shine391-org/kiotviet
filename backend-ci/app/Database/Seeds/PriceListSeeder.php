<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        // 1. Clean old data
        $this->db->table('price_list_items')->truncate();
        $this->db->table('price_lists')->truncate();

        // 2. Create Price Lists
        $data = [
            [
                'id' => 1,
                'name' => 'Bảng giá chung',
                'type' => 'default',
                'is_system' => 1,
                'is_active' => 1,
                'start_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'name' => 'Bảng giá VIP (Giảm 10%)',
                'type' => 'normal',
                'is_system' => 0,
                'is_active' => 1,
                'start_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->db->table('price_lists')->insertBatch($data);

        // 3. Create Price List Items (for all seeded products)
        // We know IDs are 501-520
        $items = [];
        $now = date('Y-m-d H:i:s');
        
        // Fetch to get exact prices
        $prods = $this->db->table('products')->get()->getResultArray();

        foreach ($prods as $p) {
            $basePrice = $p['selling_price'];
            
            // List 1: Base Price
            $items[] = [
                'price_list_id' => 1,
                'product_id' => $p['id'],
                'variant_id' => null,
                'price' => $basePrice,
                'created_at' => $now,
                'updated_at' => $now
            ];

            // List 2: VIP Price (-10%)
            $items[] = [
                'price_list_id' => 2,
                'product_id' => $p['id'],
                'variant_id' => null,
                'price' => $basePrice * 0.9,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        if (!empty($items)) {
            $this->db->table('price_list_items')->insertBatch($items);
        }

        $this->db->enableForeignKeyChecks();
        echo "✅ Seeded 2 Price Lists & " . count($items) . " Price Items.\n";
    }
}
