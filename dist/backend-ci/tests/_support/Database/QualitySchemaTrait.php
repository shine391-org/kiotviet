<?php

namespace Tests\Support\Database;

/**
 * @agent-test-helper: Reset schema for quality inspections
 * @agent-pattern: Truncate-only schema reset
 * @agent-reusable: HIGH
 */
trait QualitySchemaTrait
{
    protected function resetQualitySchema(): void
    {
        $db = property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
        $tables = ['quality_inspection_items', 'quality_inspections', 'quality_parameters'];

        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (in_array($table, $db->listTables(), true)) {
                $db->table($table)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
