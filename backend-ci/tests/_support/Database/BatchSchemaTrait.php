<?php

namespace Tests\Support\Database;

/**
 * Reset Product Batch-related tables for tests.
 *
 * @agent-test-support: Batch schema reset
 * @agent-pattern: Truncate with FK disable
 */
trait BatchSchemaTrait
{
    protected function resetBatchSchema(): void
    {
        $tables = [
            'inventory_movements',
            'stock_ledger',
            'product_batches',
            'inventory',
            'product_variants_v2',
            'products',
            'branches',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function createProductForBatch(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_type' => 'goods',
            'code' => 'PROD-' . uniqid(),
            'name' => 'Test Product ' . uniqid(),
            'slug' => 'prod-' . uniqid(),
            'status' => 'active',
            'selling_price' => 100000,
            'purchase_price' => 50000,
            'stock_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('products')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createBranchForBatch(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'Test Branch ' . uniqid(),
            'code' => 'BR-' . uniqid(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('branches')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createBatch(int $productId, int $branchId, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'batch_number' => 'BATCH-' . uniqid(),
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'cost_per_unit' => 50000,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('product_batches')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }
}
