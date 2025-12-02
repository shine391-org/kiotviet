<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Seed VAT templates (0%, 5%, 10%) for demo environments.
 *
 * @agent-seeder: Tax templates demo data
 * @agent-pattern: Idempotent insertBatch with fixed IDs
 * @agent-reusable: MEDIUM
 */
class TaxTemplatesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('tax_templates')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $rows = [
            ['id' => 9001, 'name' => 'VAT 0%',  'rate_percent' => 0.000, 'is_inclusive' => 0, 'rounding_rule' => 'nearest', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9002, 'name' => 'VAT 5%',  'rate_percent' => 5.000, 'is_inclusive' => 0, 'rounding_rule' => 'nearest', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9003, 'name' => 'VAT 10%', 'rate_percent' => 10.000, 'is_inclusive' => 0, 'rounding_rule' => 'nearest', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->db->table('tax_templates')->ignore(true)->insertBatch($rows);
    }
}
