<?php

namespace Tests\Support\Database;

/**
 * @agent-test-helper: Reset schema for manufacturing (BOM & Work Orders)
 * @agent-pattern: Truncate-only schema reset
 * @agent-reusable: HIGH
 */
trait ManufacturingSchemaTrait
{
    protected function resetManufacturingSchema(): void
    {
        $db = property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
        $tables = [
            'bom_items',
            'bill_of_materials',
            'work_orders',
            'stock_ledgers',
            'stock_bins',
            'products',
            'branches',
        ];

        $existing = array_flip($db->listTables());
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $db->table($table)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
