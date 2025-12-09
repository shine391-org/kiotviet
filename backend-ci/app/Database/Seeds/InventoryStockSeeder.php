<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seed inventory stock for products in warehouse 1.
 * Required for POS flow to validate stock availability.
 *
 * @agent-seeder: Inventory Stock
 * @agent-pattern: Insert stock for all products
 * @agent-reusable: HIGH
 */
class InventoryStockSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('inventory_stock')) {
            echo "⚠️  Table inventory_stock does not exist, skipping.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $warehouseId = 1;
        $branchId = 1;
        $defaultQty = 50;

        // Get all products
        $products = $this->db->table('products')
            ->select('id, has_variants')
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        if (empty($products)) {
            echo "⚠️  No products found to seed inventory.\n";
            return;
        }

        $stockRecords = [];

        foreach ($products as $product) {
            $productId = (int) $product['id'];
            $hasVariants = (int) ($product['has_variants'] ?? 0);

            if ($hasVariants) {
                // Get variants for this product
                $variants = $this->db->table('product_variants_v2')
                    ->select('id')
                    ->where('product_id', $productId)
                    ->where('status', 'active')
                    ->get()
                    ->getResultArray();

                foreach ($variants as $variant) {
                    $stockRecords[] = [
                        'product_id' => $productId,
                        'variant_id' => (int) $variant['id'],
                        'warehouse_id' => $warehouseId,
                        'branch_id' => $branchId,
                        'quantity_on_hand' => $defaultQty,
                        'quantity_reserved' => 0,
                        'minimum_stock' => 10,
                        'last_movement_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } else {
                // Product without variants
                $stockRecords[] = [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'warehouse_id' => $warehouseId,
                    'branch_id' => $branchId,
                    'quantity_on_hand' => $defaultQty,
                    'quantity_reserved' => 0,
                    'minimum_stock' => 10,
                    'last_movement_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($stockRecords)) {
            echo "⚠️  No stock records to insert.\n";
            return;
        }

        // Clear old demo stock first (optional - keeps idempotent)
        $this->db->table('inventory_stock')
            ->where('warehouse_id', $warehouseId)
            ->delete();

        // Insert new stock records (ignore duplicates)
        $this->db->table('inventory_stock')->ignore(true)->insertBatch($stockRecords);

        echo "✅ Seeded " . count($stockRecords) . " inventory stock records.\n";
    }
}
