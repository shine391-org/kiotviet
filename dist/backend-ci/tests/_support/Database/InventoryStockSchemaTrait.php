<?php

namespace Tests\Support\Database;

trait InventoryStockSchemaTrait
{
    protected function resetInventoryStockSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = ['inventory_stock', 'inventory_alerts'];
        $this->truncateTables($db, $tables);
    }

    private function getSchemaDb()
    {
        return property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
    }

    private function truncateTables($db, array $tables): void
    {
        $existing = array_flip($db->listTables());
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            if (isset($existing[$t])) {
                $db->table($t)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
