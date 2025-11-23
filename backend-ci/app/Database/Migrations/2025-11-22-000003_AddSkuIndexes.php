<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Add supporting indexes for code/SKU cross-validation. @agent-migration: Index optimization @agent-pattern: Cross-table uniqueness support */
class AddSkuIndexes extends Migration
{
    public function up()
    {
        $productsTable = $this->db->prefixTable('products');
        $variantsTable = $this->db->prefixTable('product_variants_v2');

        if (! $this->indexExists($productsTable, 'idx_products_code_deleted_at')) {
            $this->db->query("CREATE INDEX idx_products_code_deleted_at ON {$productsTable} (code, deleted_at)");
        }

        if (! $this->indexExists($variantsTable, 'idx_variants_sku_deleted_at')) {
            $this->db->query("CREATE INDEX idx_variants_sku_deleted_at ON {$variantsTable} (sku, deleted_at)");
        }
    }

    public function down()
    {
        $productsTable = $this->db->prefixTable('products');
        $variantsTable = $this->db->prefixTable('product_variants_v2');

        if ($this->indexExists($productsTable, 'idx_products_code_deleted_at')) {
            $this->dropIndex($productsTable, 'idx_products_code_deleted_at');
        }

        if ($this->indexExists($variantsTable, 'idx_variants_sku_deleted_at')) {
            $this->dropIndex($variantsTable, 'idx_variants_sku_deleted_at');
        }
    }

    /** Check index existence per driver. */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = strtolower($this->db->DBDriver);

        if ($driver === 'sqlite3') {
            $rows = $this->db->query("PRAGMA index_list('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                // SQLite returns 'name' column; keep loose check for safety
                if (($row['name'] ?? '') === $indexName) { return true; }
            }
            return false;
        }

        // Default: MySQL/MariaDB path
        $escaped = $this->db->escape($indexName);
        $query = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = {$escaped}");
        return $query->getNumRows() > 0;
    }

    /** Drop index with driver-aware syntax. */
    private function dropIndex(string $table, string $indexName): void
    {
        $driver = strtolower($this->db->DBDriver);
        if ($driver === 'sqlite3') {
            $this->db->query("DROP INDEX IF EXISTS {$indexName}");
            return;
        }

        $this->db->query("DROP INDEX {$indexName} ON {$table}");
    }
}
