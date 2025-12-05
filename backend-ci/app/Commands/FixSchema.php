<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixSchema extends BaseCommand
{
    protected $group       = 'Fix';
    protected $name        = 'fix:schema';
    protected $description = 'Fix Schema Manually';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        try {
            $db->query("ALTER TABLE price_lists ADD COLUMN is_system TINYINT(1) DEFAULT 0 AFTER is_active");
            CLI::write("Added is_system column successfully.", 'green');
        } catch (\Exception $e) {
            CLI::write("Error (Column might exist): " . $e->getMessage(), 'yellow');
        }
    }
}
