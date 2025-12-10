<?php
/**
 * Debug script to check customer debt data
 */
namespace App\Debug;

// Boot CodeIgniter
$_SERVER['CI_ENVIRONMENT'] = 'development';
require_once __DIR__ . '/vendor/codeigniter4/framework/system/Boot.php';

// Bootstrap the application
$app = \Config\Services::codeigniter();
$app->initialize();

// Get database connection
$db = \Config\Database::connect();

echo "=== CHECKING customer_debt_transactions TABLE ===\n";

// Check if table exists
if (!$db->tableExists('customer_debt_transactions')) {
    echo "❌ Table customer_debt_transactions does NOT exist!\n";
    exit(1);
}
echo "✓ Table exists\n";

// Count records
$count = $db->table('customer_debt_transactions')->countAllResults();
echo "Total records: {$count}\n\n";

if ($count === 0) {
    echo "❌ No data in table!\n";
    exit(1);
}

// Get sample data
echo "=== SAMPLE DATA ===\n";
$rows = $db->table('customer_debt_transactions')
    ->select('id, customer_id, code, type, value, deleted_at')
    ->limit(10)
    ->get()
    ->getResultArray();
    
foreach ($rows as $row) {
    echo sprintf("ID: %d | CustomerID: %d | Code: %s | Type: %s | Value: %s | DeletedAt: %s\n",
        $row['id'],
        $row['customer_id'],
        $row['code'],
        $row['type'],
        $row['value'],
        $row['deleted_at'] ?? 'NULL'
    );
}

echo "\n=== TESTING QUERY FOR CUSTOMER 2014 ===\n";
// Test the exact query that findDebtsByCustomerId uses
$customerId = 2014;

$debtQuery = $db->table('customer_debt_transactions')
    ->select("code, created_at, type as source_type, value, balance")
    ->where('customer_id', $customerId)
    ->where('deleted_at IS NULL');

$sql = $debtQuery->getCompiledSelect();
echo "Query: {$sql}\n";

$result = $db->query($sql)->getResultArray();
echo "Results for customer {$customerId}: " . count($result) . " rows\n";
print_r($result);

// Also check which customer_ids actually have data
echo "\n=== CUSTOMER IDs WITH DEBT DATA ===\n";
$customerIds = $db->table('customer_debt_transactions')
    ->select('customer_id, COUNT(*) as cnt')
    ->where('deleted_at IS NULL')
    ->groupBy('customer_id')
    ->get()
    ->getResultArray();
print_r($customerIds);
