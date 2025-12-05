<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ShowSchema extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:schema';
    protected $description = 'Show create table statement';
    protected $usage       = 'db:schema [table_name]';

    public function run(array $params)
    {
        $table = $params[0] ?? 'partners';
        $db = \Config\Database::connect();
        $query = $db->query("SHOW CREATE TABLE $table");
        $row = $query->getRowArray();
        CLI::write($row['Create Table']);
    }
}
