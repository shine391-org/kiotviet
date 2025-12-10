<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Debug command to verify customer debt data
 * Usage: php spark debug:debt
 */
class DebugDebt extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'debug:debt';
    protected $description = 'Debug customer debt data';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write("=== CHECKING customer_debt_transactions TABLE ===", 'yellow');

        if (!$db->tableExists('customer_debt_transactions')) {
            CLI::error("❌ Table customer_debt_transactions does NOT exist!");
            return;
        }
        CLI::write("✓ Table exists", 'green');

        // Count records
        $count = $db->table('customer_debt_transactions')->countAllResults();
        CLI::write("Total records: {$count}", 'white');

        if ($count === 0) {
            CLI::error("❌ No data in table!");
            return;
        }

        CLI::write("\n=== SAMPLE DATA ===", 'yellow');
        $rows = $db->table('customer_debt_transactions')
            ->select('id, customer_id, code, type, value, deleted_at')
            ->limit(10)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            CLI::write(sprintf(
                "ID: %d | CustomerID: %d | Code: %s | Type: %s | Value: %s | DeletedAt: %s",
                $row['id'],
                $row['customer_id'],
                $row['code'],
                $row['type'],
                $row['value'],
                $row['deleted_at'] ?? 'NULL'
            ), 'white');
        }

        CLI::write("\n=== CUSTOMER IDs WITH DEBT DATA ===", 'yellow');
        $customerIds = $db->table('customer_debt_transactions')
            ->select('customer_id, COUNT(*) as cnt')
            ->where('deleted_at IS NULL')
            ->groupBy('customer_id')
            ->get()
            ->getResultArray();

        foreach ($customerIds as $c) {
            CLI::write("Customer ID: {$c['customer_id']} has {$c['cnt']} transactions", 'white');
        }

        // Test get customer code mapping
        CLI::write("\n=== CUSTOMER CODE TO ID MAPPING ===", 'yellow');
        $customers = $db->table('customers')
            ->select('id, code, name, current_debt')
            ->whereIn('id', array_column($customerIds, 'customer_id'))
            ->limit(10)
            ->get()
            ->getResultArray();

        foreach ($customers as $c) {
            CLI::write("ID: {$c['id']} | Code: {$c['code']} | Name: {$c['name']} | Debt: {$c['current_debt']}", 'white');
        }

        // Test repository findDebtsByCustomerId for customer 2014
        CLI::write("\n=== TESTING findDebtsByCustomerId FOR CUSTOMER 2014 ===", 'yellow');
        $repo = new \App\Repositories\Customers\CustomerRepository();
        try {
            $debts = $repo->findDebtsByCustomerId(2014);
            CLI::write("Found " . count($debts) . " debts:", 'green');
            foreach ($debts as $debt) {
                CLI::write(json_encode($debt), 'white');
            }
        } catch (\Throwable $e) {
            CLI::error("Error: " . $e->getMessage());
        }
    }
}
