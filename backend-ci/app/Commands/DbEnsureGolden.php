<?php

namespace App\Commands;

use App\Database\Migrations\TestSchemaSetup;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Ensure golden schema tables exist without dropping existing data.
 *
 * Usage: php spark db:ensure-golden --groups=tests,default
 */
class DbEnsureGolden extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:ensure-golden';
    protected $description = 'Create missing tables from golden schema (TestSchemaSetup) without dropping data.';
    protected $usage       = 'db:ensure-golden [--groups=tests,default]';

    public function run(array $params)
    {
        $groupsArg = $params['groups'] ?? CLI::getOption('groups') ?? 'tests';
        $groups = array_filter(array_map('trim', explode(',', $groupsArg)));
        if (empty($groups)) {
            $groups = ['tests'];
        }

        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';

        foreach ($groups as $group) {
            CLI::write("Ensuring golden tables for group: {$group}", 'yellow');
            try {
                (new TestSchemaSetup())
                    ->setGroup($group)
                    ->ensureMissingTables();
                CLI::write("✓ Completed for group {$group}", 'green');
            } catch (\Throwable $e) {
                CLI::error("✗ Failed for group {$group}: " . $e->getMessage());
                return;
            }
        }
    }
}
