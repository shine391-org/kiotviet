<?php

namespace Tests\Support\Database;

trait StatusSchemaTrait
{
    protected function resetStatusSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = [
            'inventory_stock',
            'inventory_movements',
            'order_status_logs',
            'order_items',
            'orders',
            'cash_transactions',
            'branches',
            'users'
        ];
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
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $db->table($table)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
