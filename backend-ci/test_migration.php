<?php
// Define constants
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);

require __DIR__ . '/vendor/autoload.php';
require realpath($pathsConfig) ?: $pathsConfig;
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

// Force environment to testing
putenv('CI_ENVIRONMENT=testing');
$_SERVER['CI_ENVIRONMENT'] = 'testing';

// Connect to test database
$db = \Config\Database::connect('tests');
echo "Connected to database: " . $db->getDatabase() . "\n";

// Run migration
require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
echo "Starting migration...\n";

try {
    $migration = new \App\Database\Migrations\TestSchemaSetup();
    $migration->up();
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Check if cash_transactions exists
$tables = $db->listTables();
echo "\nTables created: " . count($tables) . "\n";
echo in_array('cash_transactions', $tables) ? "✓ cash_transactions EXISTS\n" : "✗ cash_transactions NOT FOUND\n";
echo in_array('branches', $tables) ? "✓ branches EXISTS\n" : "✗ branches NOT FOUND\n";
echo in_array('users', $tables) ? "✓ users EXISTS\n" : "✗ users NOT FOUND\n";
