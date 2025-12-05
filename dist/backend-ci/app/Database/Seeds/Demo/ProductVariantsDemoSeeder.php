<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Bổ sung sản phẩm demo có nhiều biến thể (màu + size) cho FE/QA.
 *
 * @agent-seeder: Product multi-variant demo
 * @agent-pattern: Idempotent insertBatch với cleanup theo code
 * @agent-reusable: MEDIUM
 */
class ProductVariantsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('products')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // Bổ sung option màu mới nếu chưa có
        if ($this->db->tableExists('product_attribute_options')) {
            $this->db->table('product_attribute_options')->ignore(true)->insertBatch([
                ['id' => 305, 'attribute_id' => 201, 'option_name' => 'Xanh rêu', 'color_code' => '#556b2f', 'sort_order' => 3, 'status' => 'active', 'created_at' => $now],
            ]);
        }

        $products = [
            [
                'id' => 510,
                'product_type' => 'goods',
                'code' => 'TSHIRT-PERF',
                'barcode' => 'TSHIRT-PERF-001',
                'name' => 'Áo thun Performance',
                'slug' => 'ao-thun-performance-tshirt-perf',
                'brand' => 'Lano',
                'unit' => 'Cái',
                'purchase_price' => 180000,
                'selling_price' => 450000,
                'wholesale_price' => 400000,
                'stock_quantity' => 0,
                'alert_stock' => 10,
                'has_variants' => 1,
                'image' => 'https://picsum.photos/seed/tshirt-perf/600/600',
                'images' => '[]',
                'weight' => 0,
                'dimensions' => null,
                'description' => 'Áo thun hiệu suất cao, thoáng khí, nhiều màu/size.',
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 511,
                'product_type' => 'goods',
                'code' => 'BALO-URBAN',
                'barcode' => 'BALO-URBAN-001',
                'name' => 'Balo Urban đa năng',
                'slug' => 'balo-urban-da-nang-balo-urban',
                'brand' => 'Lano',
                'unit' => 'Cái',
                'purchase_price' => 520000,
                'selling_price' => 1250000,
                'wholesale_price' => 1100000,
                'stock_quantity' => 0,
                'alert_stock' => 8,
                'has_variants' => 1,
                'image' => 'https://picsum.photos/seed/balo-urban/600/600',
                'images' => '[]',
                'weight' => 0,
                'dimensions' => null,
                'description' => 'Balo city 17\" chống nước, nhiều màu + 2 kích cỡ.',
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $variants = [
            // TSHIRT-PERF (màu đen/nâu x size M/L)
            ['id' => 8001, 'product_id' => 510, 'variant_name' => 'TSHIRT Đen M',  'variant_signature' => 'TSHIRT-PERF-DEN-M',  'sku' => 'TSHIRT-DEN-M',  'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 15, 'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8002, 'product_id' => 510, 'variant_name' => 'TSHIRT Đen L',  'variant_signature' => 'TSHIRT-PERF-DEN-L',  'sku' => 'TSHIRT-DEN-L',  'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 12, 'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8003, 'product_id' => 510, 'variant_name' => 'TSHIRT Nâu M',  'variant_signature' => 'TSHIRT-PERF-NAU-M',  'sku' => 'TSHIRT-NAU-M',  'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 10, 'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8004, 'product_id' => 510, 'variant_name' => 'TSHIRT Nâu L',  'variant_signature' => 'TSHIRT-PERF-NAU-L',  'sku' => 'TSHIRT-NAU-L',  'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 9,  'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8005, 'product_id' => 510, 'variant_name' => 'TSHIRT Xanh rêu M', 'variant_signature' => 'TSHIRT-PERF-XR-M', 'sku' => 'TSHIRT-XR-M', 'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 11, 'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8006, 'product_id' => 510, 'variant_name' => 'TSHIRT Xanh rêu L', 'variant_signature' => 'TSHIRT-PERF-XR-L', 'sku' => 'TSHIRT-XR-L', 'price' => 450000, 'cost_price' => 180000, 'stock_quantity' => 8, 'min_stock' => 5, 'max_stock' => 200, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            // BALO-URBAN (màu đen/nâu x size M/L)
            ['id' => 8011, 'product_id' => 511, 'variant_name' => 'Balo Urban Đen M', 'variant_signature' => 'BALO-URBAN-DEN-M', 'sku' => 'BALO-DEN-M', 'price' => 1250000, 'cost_price' => 520000, 'stock_quantity' => 18, 'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8012, 'product_id' => 511, 'variant_name' => 'Balo Urban Đen L', 'variant_signature' => 'BALO-URBAN-DEN-L', 'sku' => 'BALO-DEN-L', 'price' => 1320000, 'cost_price' => 520000, 'stock_quantity' => 14, 'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8013, 'product_id' => 511, 'variant_name' => 'Balo Urban Nâu M', 'variant_signature' => 'BALO-URBAN-NAU-M', 'sku' => 'BALO-NAU-M', 'price' => 1280000, 'cost_price' => 520000, 'stock_quantity' => 11, 'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8014, 'product_id' => 511, 'variant_name' => 'Balo Urban Nâu L', 'variant_signature' => 'BALO-URBAN-NAU-L', 'sku' => 'BALO-NAU-L', 'price' => 1350000, 'cost_price' => 520000, 'stock_quantity' => 9,  'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8015, 'product_id' => 511, 'variant_name' => 'Balo Urban Xanh rêu M', 'variant_signature' => 'BALO-URBAN-XR-M', 'sku' => 'BALO-XR-M', 'price' => 1280000, 'cost_price' => 520000, 'stock_quantity' => 10, 'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8016, 'product_id' => 511, 'variant_name' => 'Balo Urban Xanh rêu L', 'variant_signature' => 'BALO-URBAN-XR-L', 'sku' => 'BALO-XR-L', 'price' => 1360000, 'cost_price' => 520000, 'stock_quantity' => 8, 'min_stock' => 4, 'max_stock' => 120, 'image_url' => null, 'attributes' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ];

        $images = [
            ['id' => 9101, 'product_id' => 510, 'variant_id' => 8001, 'image_url' => 'https://picsum.photos/seed/tshirt-den/500/500', 'image_path' => '/uploads/products/tshirt-den-m.jpg', 'is_primary' => 1, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9102, 'product_id' => 510, 'variant_id' => 8003, 'image_url' => 'https://picsum.photos/seed/tshirt-nau/500/500', 'image_path' => '/uploads/products/tshirt-nau-m.jpg', 'is_primary' => 1, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9103, 'product_id' => 511, 'variant_id' => 8011, 'image_url' => 'https://picsum.photos/seed/balo-den/500/500', 'image_path' => '/uploads/products/balo-den-m.jpg', 'is_primary' => 1, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9104, 'product_id' => 511, 'variant_id' => 8013, 'image_url' => 'https://picsum.photos/seed/balo-nau/500/500', 'image_path' => '/uploads/products/balo-nau-m.jpg', 'is_primary' => 1, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9105, 'product_id' => 510, 'variant_id' => 8005, 'image_url' => 'https://picsum.photos/seed/tshirt-xanh/500/500', 'image_path' => '/uploads/products/tshirt-xanh-m.jpg', 'is_primary' => 0, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9106, 'product_id' => 511, 'variant_id' => 8015, 'image_url' => 'https://picsum.photos/seed/balo-xanh/500/500', 'image_path' => '/uploads/products/balo-xanh-m.jpg', 'is_primary' => 0, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Attribute values: color (attribute_id=201), size (attribute_id=202)
        $attrValues = [
            ['id' => 9601, 'product_id' => 510, 'variant_id' => 8001, 'attribute_id' => 201, 'option_id' => 301, 'created_at' => $now, 'updated_at' => $now], // Đen
            ['id' => 9602, 'product_id' => 510, 'variant_id' => 8001, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now], // M
            ['id' => 9603, 'product_id' => 510, 'variant_id' => 8002, 'attribute_id' => 201, 'option_id' => 301, 'created_at' => $now, 'updated_at' => $now], // Đen
            ['id' => 9604, 'product_id' => 510, 'variant_id' => 8002, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now], // L
            ['id' => 9605, 'product_id' => 510, 'variant_id' => 8003, 'attribute_id' => 201, 'option_id' => 302, 'created_at' => $now, 'updated_at' => $now], // Nâu
            ['id' => 9606, 'product_id' => 510, 'variant_id' => 8003, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now], // M
            ['id' => 9607, 'product_id' => 510, 'variant_id' => 8004, 'attribute_id' => 201, 'option_id' => 302, 'created_at' => $now, 'updated_at' => $now], // Nâu
            ['id' => 9608, 'product_id' => 510, 'variant_id' => 8004, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now], // L
            ['id' => 9611, 'product_id' => 511, 'variant_id' => 8011, 'attribute_id' => 201, 'option_id' => 301, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9612, 'product_id' => 511, 'variant_id' => 8011, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9613, 'product_id' => 511, 'variant_id' => 8012, 'attribute_id' => 201, 'option_id' => 301, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9614, 'product_id' => 511, 'variant_id' => 8012, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9615, 'product_id' => 511, 'variant_id' => 8013, 'attribute_id' => 201, 'option_id' => 302, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9616, 'product_id' => 511, 'variant_id' => 8013, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9617, 'product_id' => 511, 'variant_id' => 8014, 'attribute_id' => 201, 'option_id' => 302, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9618, 'product_id' => 511, 'variant_id' => 8014, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9619, 'product_id' => 510, 'variant_id' => 8005, 'attribute_id' => 201, 'option_id' => 305, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9620, 'product_id' => 510, 'variant_id' => 8005, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9621, 'product_id' => 510, 'variant_id' => 8006, 'attribute_id' => 201, 'option_id' => 305, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9622, 'product_id' => 510, 'variant_id' => 8006, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9623, 'product_id' => 511, 'variant_id' => 8015, 'attribute_id' => 201, 'option_id' => 305, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9624, 'product_id' => 511, 'variant_id' => 8015, 'attribute_id' => 202, 'option_id' => 303, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9625, 'product_id' => 511, 'variant_id' => 8016, 'attribute_id' => 201, 'option_id' => 305, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9626, 'product_id' => 511, 'variant_id' => 8016, 'attribute_id' => 202, 'option_id' => 304, 'created_at' => $now, 'updated_at' => $now],
        ];

        $categoryLinks = [
            ['product_id' => 510, 'category_id' => 101, 'created_at' => $now],
            ['product_id' => 511, 'category_id' => 101, 'created_at' => $now],
        ];

        // Cleanup old demo rows
        $productIds = array_column($products, 'id');
        $productCodes = array_column($products, 'code');
        $variantIds = array_column($variants, 'id');
        $this->db->table('product_attribute_values')->whereIn('product_id', $productIds)->delete();
        $this->db->table('product_images')->whereIn('product_id', $productIds)->delete();
        $this->db->table('product_variants_v2')->whereIn('id', $variantIds)->orWhereIn('product_id', $productIds)->delete();
        $this->db->table('product_category_links')->whereIn('product_id', $productIds)->delete();
        $this->db->table('products')->groupStart()->whereIn('id', $productIds)->orWhereIn('code', $productCodes)->groupEnd()->delete();

        // Insert fresh demo data
        $this->db->table('products')->ignore(true)->insertBatch($products);
        $this->db->table('product_variants_v2')->ignore(true)->insertBatch($variants);
        $this->db->table('product_images')->ignore(true)->insertBatch($images);
        $this->db->table('product_attribute_values')->ignore(true)->insertBatch($attrValues);
        $this->db->table('product_category_links')->ignore(true)->insertBatch($categoryLinks);
    }
}
