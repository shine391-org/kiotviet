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

        $products = [];
        $variants = [];
        $images = [];
        $prices = []; // Legacy product_prices
        $attrValues = [];
        $categoryLinks = [];

        // Generate 30 products
        for ($i = 1; $i <= 30; $i++) {
            $code = 'PROD-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $productId = 500 + $i;
            
            $products[] = [
                'id' => $productId,
                'product_type' => 'goods',
                'code' => $code,
                'barcode' => $code . '-BAR',
                'name' => 'Sản phẩm Demo ' . $i,
                'slug' => 'san-pham-demo-' . $i,
                'brand' => 'Lano',
                'unit' => 'Cái',
                'purchase_price' => 100000 + ($i * 1000),
                'selling_price' => 200000 + ($i * 2000),
                'wholesale_price' => 180000 + ($i * 1800),
                'stock_quantity' => 100,
                'alert_stock' => 10,
                'has_variants' => 1,
                'image' => 'https://picsum.photos/seed/' . $code . '/600/600',
                'images' => '[]',
                'weight' => 0.5,
                'dimensions' => '10x10x10',
                'description' => 'Mô tả sản phẩm demo ' . $i,
                'content' => null,
                'is_active' => 1,
                'is_available_online' => 1,
                'is_featured' => ($i % 5 == 0) ? 1 : 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
                // New columns
                'warehouse_location' => 'A-' . $i,
                'unit_conversion' => 1,
                'commission_percent' => 5,
                'commission_amount' => 0,
                'expiry_days' => 365,
                'customer_ordered' => 0,
                'expected_out_date' => null,
                'min_stock_alert' => 5,
                'max_stock_alert' => 1000,
                'related_product_codes' => null,
                'image_count' => 1,
            ];

            // Category Link (dùng category IDs từ CategorySeeder: 11, 12, 13, 21, 31, 32)
            $catIds = [11, 12, 13, 21, 31, 32];
            $categoryLinks[] = ['product_id' => $productId, 'category_id' => $catIds[$i % count($catIds)], 'created_at' => $now];

            // Legacy Price (Base)
            $prices[] = [
                'product_id' => $productId,
                'variant_id' => null,
                'customer_group_id' => null,
                'price_type' => 'retail',
                'price' => 200000 + ($i * 2000),
                'min_quantity' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Generate 2 variants per product
            for ($j = 1; $j <= 2; $j++) {
                $variantId = ($productId * 100) + $j;
                $sku = $code . '-V' . $j;
                
                $variants[] = [
                    'id' => $variantId,
                    'product_id' => $productId,
                    'variant_name' => 'Variant ' . $j,
                    'variant_signature' => $sku,
                    'sku' => $sku,
                    'barcode' => $sku . '-BAR',
                    'price' => 200000 + ($i * 2000) + ($j * 10000),
                    'cost_price' => 100000 + ($i * 1000),
                    'stock_quantity' => 50,
                    'min_stock' => 5,
                    'max_stock' => 500,
                    'image_url' => null,
                    'attributes' => json_encode(['Size' => $j == 1 ? 'M' : 'L']),
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Variant Image
                $images[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'image_url' => 'https://picsum.photos/seed/' . $sku . '/500/500',
                    'image_path' => '/uploads/products/' . $sku . '.jpg',
                    'is_primary' => ($j == 1) ? 1 : 0,
                    'sort_order' => $j,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Legacy Price (Variant)
                $prices[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'customer_group_id' => null,
                    'price_type' => 'retail',
                    'price' => 200000 + ($i * 2000) + ($j * 10000),
                    'min_quantity' => 1,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Cleanup old demo rows
        $productIds = array_column($products, 'id');
        $this->db->table('product_attribute_values')->whereIn('product_id', $productIds)->delete();
        $this->db->table('product_images')->whereIn('product_id', $productIds)->delete();
        $this->db->table('product_prices')->whereIn('product_id', $productIds)->delete(); // Cleanup prices
        $this->db->table('product_variants_v2')->whereIn('product_id', $productIds)->delete();
        $this->db->table('product_category_links')->whereIn('product_id', $productIds)->delete();
        $this->db->table('products')->whereIn('id', $productIds)->delete();

        // Insert fresh demo data
        $this->db->table('products')->ignore(true)->insertBatch($products);
        $this->db->table('product_variants_v2')->ignore(true)->insertBatch($variants);
        $this->db->table('product_images')->ignore(true)->insertBatch($images);
        $this->db->table('product_category_links')->ignore(true)->insertBatch($categoryLinks);
        
        // Insert Legacy Prices
        if ($this->db->tableExists('product_prices')) {
            $this->db->table('product_prices')->ignore(true)->insertBatch($prices);
        }
    }
}
