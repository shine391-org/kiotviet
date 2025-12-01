<?php

namespace App\Commands;

use App\Database\Migrations\TestSchemaSetup;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Database health check command.
 *
 * Usage:
 *   php spark db:health            # default group
 *   php spark db:health tests      # specific group
 */
class DbHealth extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:health';
    protected $description = 'Validate schema completeness against golden migration.';
    protected $usage       = 'db:health [group]';

    public function run(array $params)
    {
        if (! class_exists(TestSchemaSetup::class)) {
            require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        }
        $group = $params[0] ?? config(Database::class)->defaultGroup;
        $db    = Database::connect($group);

        CLI::write("Checking database group: {$group}", 'yellow');
        $expected = TestSchemaSetup::expectedTables();
        $existing = array_flip($db->listTables());

        $missing = array_values(array_filter($expected, static fn ($t) => ! isset($existing[$t])));
        $extra   = array_values(array_filter(array_keys($existing), static fn ($t) => ! in_array($t, $expected, true)));

        if (empty($missing)) {
            CLI::write('Missing tables: 0', 'green');
        } else {
            CLI::write('Missing tables: ' . count($missing), 'red');
            foreach ($missing as $table) {
                CLI::write("- {$table}", 'light_red');
            }
        }

        if (! empty($extra)) {
            CLI::write('Extra tables (not in golden schema): ' . count($extra), 'yellow');
            foreach ($extra as $table) {
                CLI::write("- {$table}", 'light_yellow');
            }
        } else {
            CLI::write('Extra tables: 0', 'green');
        }

        $schemaVersion = config(Database::class)->schemaVersion ?? 'unknown';
        CLI::write("Schema version: {$schemaVersion}", 'cyan');

        if (! empty($missing)) {
            return 1;
        }

        CLI::write('Database health check passed', 'green');
        return 0;
    }
}
