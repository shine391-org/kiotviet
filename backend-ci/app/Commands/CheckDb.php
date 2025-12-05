<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class CheckDb extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:check';
    protected $description = 'Check current database connection details.';

    public function run(array $params)
    {
        $db = Database::connect('tests');
        CLI::write("Connected to Database: " . $db->database, 'green');
        CLI::write("Hostname: " . $db->hostname, 'green');
        CLI::write("Username: " . $db->username, 'green');
        
        $tables = $db->listTables();
        CLI::write("Tables count: " . count($tables), 'yellow');
        foreach ($tables as $table) {
            CLI::write(" - $table");
        }
    }
}
