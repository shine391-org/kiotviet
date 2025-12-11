<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;

/**
 * Restructure flat products into parent + variants structure
 * For WooCommerce/Shopify compatibility
 */
class RestructureProducts extends BaseCommand
{
    protected $group = 'Custom';
    protected $name = 'products:restructure';
    protected $description = 'Restructure flat products into parent + variants';

    protected BaseConnection $db;

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();
        
        CLI::write("=== Restructuring Products ===", 'yellow');
        CLI::write("This will convert flat products (VDP03-NS, VDP03-D) into parent (VDP03) + variants", 'white');
        
        // Step 1: Analyze
        $analysis = $this->analyzeProducts();
        CLI::write("Found {$analysis['groups']} product groups with {$analysis['variants']} variants", 'green');
        
        if ($analysis['groups'] == 0) {
            CLI::write("No products to restructure!", 'yellow');
            return;
        }

        // Step 2: Backup counts
        $this->showCounts('Before');

        // Step 3: Restructure
        $this->restructure();

        // Step 4: Show results
        $this->showCounts('After');
        
        CLI::write("Restructuring completed!", 'green');
    }

    private function analyzeProducts(): array
    {
        // Find all products with variant pattern (code contains -)
        $sql = "
            SELECT 
                CASE 
                    WHEN code REGEXP '-[A-Za-z0-9]+$' THEN SUBSTRING_INDEX(code, '-', 1)
                    ELSE NULL 
                END as base_code,
                COUNT(*) as cnt
            FROM products
            WHERE deleted_at IS NULL
            AND code REGEXP '-[A-Za-z0-9]+$'
            GROUP BY base_code
            HAVING cnt > 0
        ";
        
        $result = $this->db->query($sql)->getResultArray();
        $groups = count($result);
        $variants = array_sum(array_column($result, 'cnt'));
        
        return ['groups' => $groups, 'variants' => $variants];
    }

    private function showCounts(string $label): void
    {
        $products = $this->db->table('products')->where('deleted_at', null)->countAllResults();
        $variants = $this->db->table('product_variants_v2')->where('deleted_at', null)->countAllResults();
        $attrValues = $this->db->table('product_attribute_values')->where('deleted_at', null)->countAllResults();
        
        CLI::write("{$label}: Products={$products}, Variants={$variants}, AttrValues={$attrValues}", 'cyan');
    }

    private function restructure(): void
    {
        // Get all variant products grouped by base code
        $sql = "
            SELECT 
                SUBSTRING_INDEX(code, '-', 1) as base_code,
                id, code, name, barcode, selling_price, purchase_price, 
                stock_quantity, min_stock_alert, max_stock_alert, image, unit, weight,
                description
            FROM products
            WHERE deleted_at IS NULL
            AND code REGEXP '-[A-Za-z0-9]+$'
            ORDER BY base_code, code
        ";
        
        $variantProducts = $this->db->query($sql)->getResultArray();
        
        // Group by base_code
        $groups = [];
        foreach ($variantProducts as $p) {
            $groups[$p['base_code']][] = $p;
        }
        
        CLI::write("Processing " . count($groups) . " product groups...", 'white');
        
        $processed = 0;
        $variantsCreated = 0;
        
        foreach ($groups as $baseCode => $variants) {
            // Find or create parent product
            $parentId = $this->findOrCreateParent($baseCode, $variants[0]);
            
            if (!$parentId) {
                CLI::write("Failed to create parent for {$baseCode}", 'red');
                continue;
            }
            
            // Convert each variant product to product_variants_v2
            foreach ($variants as $variantProduct) {
                $this->convertToVariant($parentId, $variantProduct);
                $variantsCreated++;
            }
            
            $processed++;
            
            if ($processed % 50 == 0) {
                CLI::write("Processed {$processed} groups, {$variantsCreated} variants...", 'white');
            }
        }
        
        CLI::write("Created {$variantsCreated} variants from {$processed} groups", 'green');
    }

    private function findOrCreateParent(string $baseCode, array $sampleVariant): ?int
    {
        // Check if parent already exists
        $existing = $this->db->table('products')
            ->where('code', $baseCode)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();
        
        if ($existing) {
            return (int) $existing['id'];
        }
        
        // Create parent from sample variant
        // Remove variant suffix from name (e.g., "Ví VDP03-NS" -> "Ví VDP03")
        $name = $sampleVariant['name'];
        
        // Try to clean variant suffix from name
        $name = preg_replace('/-[A-Za-z0-9]+$/', '', $name);
        $name = preg_replace('/\s+(NS|D|NV|NC|ND|Do|Xanh|Navy|Ghi|Be|Trang|Den)$/i', '', $name);
        
        // Get categories from first variant
        $categories = $this->db->table('product_category_links')
            ->where('product_id', $sampleVariant['id'])
            ->get()
            ->getResultArray();
        
        $this->db->table('products')->insert([
            'code' => $baseCode,
            'name' => trim($name),
            'slug' => $this->createSlug($baseCode),
            'description' => $sampleVariant['description'],
            'unit' => $sampleVariant['unit'],
            'weight' => $sampleVariant['weight'],
            'image' => $sampleVariant['image'],
            'selling_price' => 0, // Will be set from variants
            'purchase_price' => 0,
            'stock_quantity' => 0, // Sum from variants
            'has_variants' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $parentId = $this->db->insertID();
        
        // Link categories to parent
        foreach ($categories as $cat) {
            $this->db->table('product_category_links')->insert([
                'product_id' => $parentId,
                'category_id' => $cat['category_id'],
            ]);
        }
        
        return $parentId;
    }

    private function convertToVariant(int $parentId, array $product): void
    {
        $productId = (int) $product['id'];
        $variantSuffix = $this->extractVariantSuffix($product['code']);
        
        // Determine variant name from suffix
        $variantName = $this->getVariantNameFromSuffix($variantSuffix);
        
        // Create variant record
        $this->db->table('product_variants_v2')->insert([
            'product_id' => $parentId,
            'variant_name' => $variantName,
            'variant_signature' => strtoupper($variantSuffix),
            'sku' => $product['code'],
            'barcode' => $product['barcode'],
            'price' => $product['selling_price'],
            'cost_price' => $product['purchase_price'],
            'stock_quantity' => $product['stock_quantity'],
            'min_stock' => $product['min_stock_alert'] ?? 0,
            'max_stock' => $product['max_stock_alert'] ?? 0,
            'image_url' => $product['image'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $variantId = $this->db->insertID();
        
        // Move attribute values from product to variant
        $this->db->table('product_attribute_values')
            ->where('product_id', $productId)
            ->update([
                'product_id' => $parentId,
                'variant_id' => $variantId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Update sales_invoice_items to reference parent (keep product_id for historical data)
        $this->db->table('sales_invoice_items')
            ->where('product_id', $productId)
            ->update(['product_id' => $parentId]);
        
        // Update purchase_order_items
        $this->db->table('purchase_order_items')
            ->where('product_id', $productId)
            ->update(['product_id' => $parentId]);
        
        // Soft delete the original product
        $this->db->table('products')
            ->where('id', $productId)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Remove category links for deleted product
        $this->db->table('product_category_links')
            ->where('product_id', $productId)
            ->delete();
    }

    private function extractVariantSuffix(string $code): string
    {
        if (preg_match('/-([A-Za-z0-9]+)$/', $code, $m)) {
            return $m[1];
        }
        return '';
    }

    private function getVariantNameFromSuffix(string $suffix): string
    {
        $map = [
            'NS' => 'Nâu sẫm',
            'D' => 'Đen',
            'Den' => 'Đen',
            'NV' => 'Nâu vàng',
            'NC' => 'Nâu cafe',
            'ND' => 'Nâu đậm',
            'Do' => 'Đỏ',
            'Xanh' => 'Xanh',
            'xanh' => 'Xanh',
            'Navy' => 'Navy',
            'navy' => 'Navy',
            'Ghi' => 'Ghi',
            'ghi' => 'Ghi',
            'Be' => 'Be',
            'be' => 'Be',
            'Trang' => 'Trắng',
            'trang' => 'Trắng',
            'N' => 'Nâu',
            'Xanhla' => 'Xanh lá',
            'xanhla' => 'Xanh lá',
            'hong' => 'Hồng',
            'eutope' => 'Eutope',
            'nb' => 'Nâu bò',
        ];
        
        return $map[$suffix] ?? ucfirst(strtolower($suffix));
    }

    private function createSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
