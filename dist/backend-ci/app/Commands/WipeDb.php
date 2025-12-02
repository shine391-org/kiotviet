<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class WipeDb extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:wipe';
    protected $description = 'Drop ALL tables in the database.';

    public function run(array $params)
    {
        $db = Database::connect('tests');
        
        CLI::write("Env Username: " . getenv('database.tests.username'), 'cyan');
        CLI::write("Config Username: " . $db->username, 'cyan');
        
        $query = $db->query("SELECT USER(), DATABASE()");
        $row = $query->getRow();
        CLI::write("Connected as: " . $row->{'USER()'}, 'magenta');
        CLI::write("Current DB: " . $row->{'DATABASE()'}, 'magenta');

        $db->query('SET FOREIGN_KEY_CHECKS=0');
        
        $db->query("DROP DATABASE IF EXISTS lanocrm_test");
        $db->query("CREATE DATABASE lanocrm_test");
        CLI::write("Database lanocrm_test dropped and recreated.", 'green');
    }
}
