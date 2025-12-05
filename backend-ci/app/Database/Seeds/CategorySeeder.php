<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        
        // Clean old data
        $this->db->table('product_category_links')->truncate();
        $this->db->table('product_categories')->truncate();

        $now = date('Y-m-d H:i:s');
        
        // Logical Categories: 
        // 1. Áo (Men, Women)
        // 2. Quần (Jeans, Kaki)
        // 3. Phụ kiện (Túi, Mũ)
        
        $categories = [
            // Level 1
            ['id' => 10, 'parent_id' => null, 'name' => 'Thời trang Nam', 'slug' => 'thoi-trang-nam', 'code' => 'CAT_MEN'],
            ['id' => 20, 'parent_id' => null, 'name' => 'Thời trang Nữ', 'slug' => 'thoi-trang-nu', 'code' => 'CAT_WOMEN'],
            ['id' => 30, 'parent_id' => null, 'name' => 'Phụ kiện', 'slug' => 'phu-kien', 'code' => 'CAT_ACCESSORIES'],
            
            // Level 2 - Men
            ['id' => 11, 'parent_id' => 10, 'name' => 'Áo Thun Nam', 'slug' => 'ao-thun-nam', 'code' => 'CAT_MEN_TSHIRT'],
            ['id' => 12, 'parent_id' => 10, 'name' => 'Áo Sơ mi Nam', 'slug' => 'ao-somi-nam', 'code' => 'CAT_MEN_SHIRT'],
            ['id' => 13, 'parent_id' => 10, 'name' => 'Quần Jeans Nam', 'slug' => 'quan-jeans-nam', 'code' => 'CAT_MEN_JEANS'],
            
            // Level 2 - Women
            ['id' => 21, 'parent_id' => 20, 'name' => 'Đầm Váy', 'slug' => 'dam-vay', 'code' => 'CAT_WOMEN_DRESS'],
            ['id' => 22, 'parent_id' => 20, 'name' => 'Áo Kiểu', 'slug' => 'ao-kieu', 'code' => 'CAT_WOMEN_TOP'],
            
            // Level 2 - Accessories
            ['id' => 31, 'parent_id' => 30, 'name' => 'Túi xách', 'slug' => 'tui-xach', 'code' => 'CAT_BAGS'],
            ['id' => 32, 'parent_id' => 30, 'name' => 'Giày dép', 'slug' => 'giay-dep', 'code' => 'CAT_SHOES'],
            ['id' => 33, 'parent_id' => 30, 'name' => 'Đồng hồ', 'slug' => 'dong-ho', 'code' => 'CAT_WATCHES'],
        ];

        $data = [];
        $i = 1;
        foreach ($categories as $cat) {
            $data[] = [
                'id' => $cat['id'],
                'parent_id' => $cat['parent_id'],
                'level' => $cat['parent_id'] ? 2 : 1,
                'code' => $cat['code'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'is_variant_group' => 0,
                'sort_order' => $i++,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('product_categories')->insertBatch($data);
        $this->db->enableForeignKeyChecks();
        
        echo "✅ Seeded " . count($data) . " Categories.\n";
    }
}
