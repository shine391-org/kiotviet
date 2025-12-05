<?php

namespace Tests\Support\Database;

trait ReturnSchemaTrait
{
    protected function resetReturnSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = ['cash_transactions','return_items','returns','order_items','orders','customers','branches','users'];
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
        foreach ($tables as $tbl) {
            if (isset($existing[$tbl])) {
                $db->table($tbl)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
