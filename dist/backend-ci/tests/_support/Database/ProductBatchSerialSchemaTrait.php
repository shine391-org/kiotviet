<?php

namespace Tests\Support\Database;

trait ProductBatchSerialSchemaTrait
{
    protected function resetProductBatchSerialSchema(): void
    {
        $db = property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
        $tables = ['product_serial_numbers', 'product_batches', 'inventory_stock', 'inventory_movements'];
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
