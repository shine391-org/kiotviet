<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class MigrateDebug extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:debug';
    protected $description = 'Debug migration connection.';

    public function run(array $params)
    {
        $migrations = Services::migrations();
        // Force group tests
        $migrations->setGroup('tests');
        
        // Access protected db property via reflection or just assume it uses the group
        $db = \Config\Database::connect('tests');
        
        CLI::write("MigrateDebug connecting to group: tests", 'yellow');
        CLI::write("Config Hostname: " . $db->hostname, 'cyan');
        CLI::write("Config Database: " . $db->database, 'cyan');
        CLI::write("Config Username: " . $db->username, 'cyan');
        
        $query = $db->query("SELECT @@hostname, DATABASE(), USER(), VERSION()");
        $row = $query->getRow();
        CLI::write("Server Hostname: " . $row->{'@@hostname'}, 'magenta');
        CLI::write("Server DB: " . $row->{'DATABASE()'}, 'magenta');
        CLI::write("Server User: " . $row->{'USER()'}, 'magenta');
        
        // Check migrations table
        $tables = $db->listTables();
        if (in_array('migrations', $tables)) {
            CLI::write("Migrations table FOUND.", 'green');
            $count = $db->table('migrations')->countAll();
            CLI::write("Migrations count: $count", 'green');
        } else {
            CLI::write("Migrations table NOT FOUND.", 'red');
        }
    }
}
