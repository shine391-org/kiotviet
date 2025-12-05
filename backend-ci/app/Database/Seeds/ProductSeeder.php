<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        
        // Clean old product data
        $this->db->table('product_category_links')->truncate();
        $this->db->table('product_images')->truncate();
        $this->db->table('product_attribute_values')->truncate();
        $this->db->table('product_variants_v2')->truncate();
        $this->db->table('products')->truncate();
        
        $this->db->enableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');
        
        // 1. Categories (Ensure they exist)
        // We assume MasterDataSeeder created 101, 102, 103. If not, this might fail on FK if strict.
        // Let's safe insert just in case.
        $cats = [
             ['id'=> 101, 'parent_id'=>null, 'level'=>1, 'code'=>'TUI', 'name'=>'Túi xách', 'slug'=>'tui-xach', 'sort_order'=>1, 'status'=>'active', 'created_at'=>$now],
             ['id'=> 102, 'parent_id'=>null, 'level'=>1, 'code'=>'VI',  'name'=>'Ví',      'slug'=>'vi',        'sort_order'=>2, 'status'=>'active', 'created_at'=>$now],
             ['id'=> 103, 'parent_id'=>101, 'level'=>2, 'code'=>'TUI-DA','name'=>'Túi da',  'slug'=>'tui-da',   'sort_order'=>1, 'status'=>'active', 'created_at'=>$now],
             ['id'=> 104, 'parent_id'=>null, 'level'=>1, 'code'=>'GIAY', 'name'=>'Giày dép', 'slug'=>'giay-dep', 'sort_order'=>3, 'status'=>'active', 'created_at'=>$now],
        ];
        $this->db->table('product_categories')->ignore(true)->insertBatch($cats);

        // 2. Real Images (Rotation)
        $imagesList = [
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg', // Túi đeo chéo
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg', // Ví da
            'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg', // Ví nhỏ
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg', // Balo
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg'  // Cặp da
        ];

        // 3. Generate 20 Products
        $products = [];
        $variants = [];
        $links = [];
        $productImages = []; // New table for detail images
        
        for ($i = 1; $i <= 20; $i++) {
            $code = 'SP' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $price = rand(100, 5000) * 1000;
            $cost = $price * 0.6;
            $catId = $cats[array_rand($cats)]['id'];
            $imgUrl = $imagesList[$i % count($imagesList)];
            
            $products[] = [
                'id' => 500 + $i,
                'product_type' => 'goods',
                'code' => $code,
                'barcode' => $code,
                'name' => "Sản phẩm Demo $i - " . ($i % 2 === 0 ? 'Cao cấp' : 'Thường'),
                'slug' => "san-pham-demo-$i",
                'brand' => 'Lano',
                'unit' => 'Cái',
                'purchase_price' => $cost,
                'selling_price' => $price,
                'stock_quantity' => rand(0, 50),
                'has_variants' => 0,
                'image' => $imgUrl,
                'images' => json_encode([$imgUrl]), // For simple array field
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Product Image (Gallery)
            $productImages[] = [
                'product_id' => 500 + $i,
                'image_url' => $imgUrl,
                'is_primary' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Category Link
            $links[] = [
                'product_id' => 500 + $i, 
                'category_id' => $catId, 
                'created_at' => $now
            ];
            
            // Variants logic
            if ($i % 5 === 0) {
                 $products[$i-1]['has_variants'] = 1;
                 
                 $variants[] = [
                    'id' => 7000 + ($i * 10) + 1,
                    'product_id' => 500 + $i,
                    'variant_name' => "Sản phẩm Demo $i - Size M",
                    'sku' => $code . '-M',
                    'price' => $price,
                    'cost_price' => $cost,
                    'stock_quantity' => 10,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now
                 ];
                 $variants[] = [
                    'id' => 7000 + ($i * 10) + 2,
                    'product_id' => 500 + $i,
                    'variant_name' => "Sản phẩm Demo $i - Size L",
                    'sku' => $code . '-L',
                    'price' => $price + 50000,
                    'cost_price' => $cost,
                    'stock_quantity' => 15,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now
                 ];
                 $products[$i-1]['stock_quantity'] = 25;
            }
        }

        $this->db->table('products')->ignore(true)->insertBatch($products);
        if (!empty($variants)) {
            $this->db->table('product_variants_v2')->ignore(true)->insertBatch($variants);
        }
        $this->db->table('product_category_links')->ignore(true)->insertBatch($links);
        $this->db->table('product_images')->ignore(true)->insertBatch($productImages);
        
        echo "Seeded 20 Products & Variants with REAL Images.\n";
    }
}
