<?php
// Set environment to testing
putenv('ENVIRONMENT=testing');
$_SERVER['CI_ENVIRONMENT'] = 'testing';
define('ENVIRONMENT', 'testing');
define('FCPATH', __DIR__ . '/public/');

require __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = new \Config\Paths();
$bootstrap = rtrim(realpath(FCPATH . '../vendor/codeigniter4/framework/system/Test/bootstrap.php') ?: FCPATH . '../vendor/codeigniter4/framework/system/Test/bootstrap.php', '/ ');
chdir(FCPATH);
require $bootstrap;

// Get test database connection
$db = \Config\Database::connect('tests');
$forge = \Config\Database::forge('tests');

echo "Connected to database: {$db->database}\n";
echo "Running BaselineSchema migration...\n\n";

// Create migration instance
$migration = new \App\Database\Migrations\BaselineSchema();
$migration->db = $db;
$migration->forge = $forge;
$migration->DBGroup = 'tests';

try {
    // Run the migration
    $migration->up();
    
    echo "✓ Migration completed successfully!\n\n";
    
    // List tables
    $tables = $db->listTables();
    echo "Total tables created: " . count($tables) . "\n";
    
    // Check for critical tables
    $criticalTables = ['orders', 'customers', 'payment_methods', 'products', 'branches'];
    echo "\nVerifying critical tables:\n";
    foreach ($criticalTables as $table) {
        $exists = in_array($table, $tables);
        echo ($exists ? "✓" : "✗") . " {$table}\n";
    }
    
} catch (\Throwable $e) {
    echo "✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✓ Test database schema is ready!\n";
