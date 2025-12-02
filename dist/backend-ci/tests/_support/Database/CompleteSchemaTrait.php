<?php

namespace Tests\Support\Database;

/**
 * CompleteSchemaTrait - truncate tất cả bảng hiện có (schema do migrations cung cấp).
 */
trait CompleteSchemaTrait
{
    protected function resetCompleteSchema(): void
    {
        $db = method_exists($this, 'getTestDb') ? $this->getTestDb() : \Config\Database::connect('tests');
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($db->listTables() as $table) {
            $db->table($table)->truncate();
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
