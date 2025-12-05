<?php
namespace App\Controllers;
use CodeIgniter\Controller;
use Config\Database;
use Config\Services;

class Repro extends Controller
{
    public function index()
    {
        $db = Database::connect('tests');
        $forge = Database::forge('tests');
        
        echo "Dropping migrations table...\n";
        $forge->dropTable('migrations', true);
        
        echo "Running migrations->latest()...\n";
        try {
            $migrations = Services::migrations();
            $migrations->setGroup('tests');
            $migrations->latest();
            echo "Success!\n";
        } catch (\Throwable $e) {
            echo "Caught exception: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
        }
    }
}
