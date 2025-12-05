<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Sản phẩm mẫu
        $products = [
            [
                'id' => 501,
                'product_type' => 'goods',
                'code' => 'TDH016',
                'barcode' => null,
                'name' => 'Túi đeo chéo da bò cao cấp khâu tay thủ công Lano TDH016',
                'slug' => 'tui-deo-cheo-da-bo-cao-cap-khau-tay-thu-cong-lano-tdh016-tdh016',
                'brand' => null,
                'unit' => 'Cái',
                'purchase_price' => 1500000,
                'selling_price' => 7500000,
                'wholesale_price' => null,
                'stock_quantity' => 8,
                'alert_stock' => 10,
                'has_variants' => 1,
                'image' => 'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',
                'images' => '[]',
                'weight' => 0,
                'dimensions' => null,
                'description' => 'Sản phẩm demo có biến thể màu/size',
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 502,
                'product_type' => 'goods',
                'code' => 'VDNTK035',
                'barcode' => null,
                'name' => 'Ví da handmade khâu tay thủ công Lano VDNTK035',
                'slug' => 'vi-da-handmade-khau-tay-thu-cong-lano-vdntk035-vdntk035',
                'brand' => null,
                'unit' => 'Cái',
                'purchase_price' => 650000,
                'selling_price' => 1950000,
                'wholesale_price' => null,
                'stock_quantity' => 10,
                'alert_stock' => 5,
                'has_variants' => 0,
                'image' => 'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg',
                'images' => '[]',
                'weight' => 0,
                'dimensions' => null,
                'description' => 'Ví da demo 1',
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 503,
                'product_type' => 'goods',
                'code' => 'VDNTK034',
                'barcode' => null,
                'name' => 'Ví da epsom italia nhỏ gọn Lano VDNTK034',
                'slug' => 'vi-da-epsom-italia-nho-gon-lano-vdntk034-vdntk034',
                'brand' => null,
                'unit' => 'Cái',
                'purchase_price' => 350000,
                'selling_price' => 1500000,
                'wholesale_price' => null,
                'stock_quantity' => 6,
                'alert_stock' => 5,
                'has_variants' => 0,
                'image' => 'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg',
                'images' => '[]',
                'weight' => 0,
                'dimensions' => null,
                'description' => 'Ví da demo 2',
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('products')->ignore(true)->insertBatch($products);

        // Biến thể
        $variants = [
            [
                'id' => 7001,
                'product_id' => 501,
                'variant_name' => 'TDH016 M Đen',
                'variant_signature' => 'TDH016-M-DEN',
                'sku' => 'TDH016-M-BLK',
                'barcode' => null,
                'price' => 7500000,
                'cost_price' => 1500000,
                'stock_quantity' => 5,
                'min_stock' => 0,
                'max_stock' => 100,
                'image_url' => null,
                'attributes' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7002,
                'product_id' => 501,
                'variant_name' => 'TDH016 L Nâu',
                'variant_signature' => 'TDH016-L-NAU',
                'sku' => 'TDH016-L-BRN',
                'barcode' => null,
                'price' => 7500000,
                'cost_price' => 1500000,
                'stock_quantity' => 3,
                'min_stock' => 0,
                'max_stock' => 100,
                'image_url' => null,
                'attributes' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('product_variants_v2')->ignore(true)->insertBatch($variants);

        // Ảnh sản phẩm/biến thể
        $images = [
            [
                'id'=>9001,
                'product_id'=>501,
                'variant_id'=>7001,
                'image_url'=>'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',
                'image_path'=>'/uploads/products/tdh016-m-den.jpg',
                'is_primary'=>1,
                'sort_order'=>0,
                'created_at'=>$now,
                'updated_at'=>$now,
            ],
            [
                'id'=>9002,
                'product_id'=>501,
                'variant_id'=>7002,
                'image_url'=>'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',
                'image_path'=>'/uploads/products/tdh016-l-nau.jpg',
                'is_primary'=>1,
                'sort_order'=>0,
                'created_at'=>$now,
                'updated_at'=>$now,
            ],
        ];
        $this->db->table('product_images')->ignore(true)->insertBatch($images);

        // Link sản phẩm với category
        $links = [
            ['product_id'=>501, 'category_id'=>103, 'created_at'=>$now],
            ['product_id'=>502, 'category_id'=>102, 'created_at'=>$now],
            ['product_id'=>503, 'category_id'=>102, 'created_at'=>$now],
        ];
        $this->db->table('product_category_links')->ignore(true)->insertBatch($links);

        // Giá trị thuộc tính cho biến thể
        $attrValues = [
            ['id'=>9501,'product_id'=>501,'variant_id'=>7001,'attribute_id'=>201,'option_id'=>301,'created_at'=>$now,'updated_at'=>$now], // Đen
            ['id'=>9502,'product_id'=>501,'variant_id'=>7001,'attribute_id'=>202,'option_id'=>303,'created_at'=>$now,'updated_at'=>$now], // M
            ['id'=>9503,'product_id'=>501,'variant_id'=>7002,'attribute_id'=>201,'option_id'=>302,'created_at'=>$now,'updated_at'=>$now], // Nâu
            ['id'=>9504,'product_id'=>501,'variant_id'=>7002,'attribute_id'=>202,'option_id'=>304,'created_at'=>$now,'updated_at'=>$now], // L
        ];
        $this->db->table('product_attribute_values')->ignore(true)->insertBatch($attrValues);
    }
}
