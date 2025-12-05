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
        $this->db->table('product_prices')->truncate();
        $this->db->table('product_variants_v2')->truncate();
        $this->db->table('products')->truncate();
        
        $this->db->enableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');
        
        // Image Pool
        $imagesList = [
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg', // Bag
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg', // Wallet
            'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg', // Vest
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg', // Backpack
            'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg'  // Office Bag
        ];

        // Categories Map (created in CategorySeeder)
        $catIds = [11, 12, 13, 21, 31, 32];

        $products = [];
        $variants = [];
        $links = [];
        $productImages = []; 
        $productPrices = []; 

        for ($i = 1; $i <= 20; $i++) {
            $id = 500 + $i;
            $code = 'SP' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $price = rand(150, 1500) * 1000; // 150k - 1.5M
            $cost = $price * 0.6;
            $catId = $catIds[array_rand($catIds)];
            $imgUrl = $imagesList[$i % count($imagesList)];
            
            $namePrefix = 'Sản phẩm';
            if ($catId == 11) $namePrefix = 'Áo Thun Nam Cao Cấp';
            if ($catId == 12) $namePrefix = 'Áo Sơ Mi Công Sở';
            if ($catId == 13) $namePrefix = 'Quần Jeans Slimfit';
            if ($catId == 21) $namePrefix = 'Đầm Dạ Hội';
            if ($catId == 31) $namePrefix = 'Túi Xách Da Thật';
            if ($catId == 32) $namePrefix = 'Giày Tây Nam';

            $name = "$namePrefix Mẫu $i";

            $products[] = [
                'id' => $id,
                'product_type' => 'goods',
                'code' => $code,
                'barcode' => $code,
                'name' => $name,
                'slug' => url_title($name, '-', true),
                'brand' => 'Lano Official',
                'unit' => 'Cái',
                'purchase_price' => $cost,
                'selling_price' => $price,
                'stock_quantity' => rand(10, 100),
                'has_variants' => 0,
                'image' => $imgUrl, // Keeping this as main thumb
                'images' => json_encode([$imgUrl]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 1. Product Price (Base Retail)
            $productPrices[] = [
                'product_id' => $id,
                'variant_id' => null,
                'price_type' => 'retail',
                'price'      => $price,
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 2. Images (Gallery)
            // Fix: ensure table has matching columns. We checked schema earlier. 
            // product_id, image_url, is_primary, sort_order
            $productImages[] = [
                'product_id' => $id,
                'image_url' => $imgUrl,
                'is_primary' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 3. Category Link
            $links[] = [
                'product_id' => $id, 
                'category_id' => $catId, 
                'created_at' => $now
            ];
            
            // 4. Create Variants for every 5th product
            if ($i % 5 === 0) {
                 // Update parent to has_variants - Note: we can't update array by reference easily in foreach loop without &
                 // So we modify the LAST element of $products array
                 $products[count($products) - 1]['has_variants'] = 1;
                 
                 // Variation 1: Size M
                 $v1_id = 7000 + ($i * 10) + 1;
                 $variants[] = [
                    'id' => $v1_id,
                    'product_id' => $id,
                    'variant_name' => "$name - Size M",
                    'sku' => $code . '-M',
                    'price' => $price,
                    'cost_price' => $cost,
                    'stock_quantity' => 20,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now
                 ];
                 // Variant 1 Price
                 $productPrices[] = [
                    'product_id' => $id,
                    'variant_id' => $v1_id,
                    'price_type' => 'retail',
                    'price'      => $price,
                    'status'     => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                 // Variation 2: Size L
                 $v2_id = 7000 + ($i * 10) + 2;
                 $variants[] = [
                    'id' => $v2_id,
                    'product_id' => $id,
                    'variant_name' => "$name - Size L",
                    'sku' => $code . '-L',
                    'price' => $price + 20000,
                    'cost_price' => $cost,
                    'stock_quantity' => 20,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now
                 ];
                 // Variant 2 Price
                 $productPrices[] = [
                    'product_id' => $id,
                    'variant_id' => $v2_id,
                    'price_type' => 'retail',
                    'price'      => $price + 20000,
                    'status'     => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->db->table('products')->insertBatch($products);
        if (!empty($variants)) {
            $this->db->table('product_variants_v2')->insertBatch($variants);
        }
        $this->db->table('product_category_links')->insertBatch($links);
        $this->db->table('product_images')->insertBatch($productImages);
        $this->db->table('product_prices')->insertBatch($productPrices);
        
        echo "✅ Seeded 20 Products with Variants, Images, Prices.\n";
    }
}
