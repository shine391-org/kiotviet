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
        $defaultQty = 50;

        // Get all warehouses with their branch_id
        $warehouses = $this->db->table('warehouses')
            ->select('id, branch_id')
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        if (empty($warehouses)) {
            // No warehouses exist - skip seeding to avoid FK constraint violations
            echo "⚠️  No active warehouses found. Skipping inventory stock seeding.\n";
            echo "   → Please create warehouses first (via WarehousesDemoSeeder or manually).\n";
            return;
        }

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

        foreach ($warehouses as $warehouse) {
            $warehouseId = (int) $warehouse['id'];
            $branchId = (int) ($warehouse['branch_id'] ?? 1);

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
        }

        if (empty($stockRecords)) {
            echo "⚠️  No stock records to insert.\n";
            return;
        }

        // Clear old stock first (idempotent - fresh start)
        $this->db->table('inventory_stock')->truncate();

        // Insert new stock records in chunks to avoid memory issues
        $chunks = array_chunk($stockRecords, 1000);
        foreach ($chunks as $chunk) {
            $this->db->table('inventory_stock')->insertBatch($chunk);
        }

        echo "✅ Seeded " . count($stockRecords) . " inventory stock records across " . count($warehouses) . " warehouses.\n";
    }
}
