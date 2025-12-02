<?php

namespace Tests\Support\Database;

/**
 * POSSchemaTrait - truncate POS tables for tests.
 */
trait POSSchemaTrait
{
    protected function resetPOSSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = [
            'pos_shift_logs',
            'pos_shift_payments',
            'pos_shifts',
            'pos_payment_methods',
            'pos_profiles',
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
        foreach ($tables as $t) {
            if (isset($existing[$t])) {
                $db->table($t)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
