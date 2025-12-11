<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master importer for KiotViet export data.
 * Run: php spark db:seed KiotVietFullImport
 */
class KiotVietFullImport extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "⚠️  KiotVietFullImport skipped in production\n";
            return;
        }

        echo "=== KiotViet Full Data Import ===\n\n";

        // Disable FK checks
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        echo "✓ Disabled FK checks\n";

        // Truncate tables in reverse dependency order
        $tables = [
            'cash_transactions',
            'stock_disposal_items',
            'stock_disposals',
            'stock_audit_items', 
            'stock_audits',
            'stock_transfer_items',
            'stock_transfers',
            'return_items',
            'returns',
            'purchase_order_items',
            'purchase_orders',
            'order_items',
            'order_payments',
            'invoices',
            'orders',
            'delivery_partners',
            'partners', // suppliers
            'customers',
            'product_variants',
            'products',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)->truncate();
                echo "  Truncated: {$table}\n";
            }
        }

        // Re-enable FK checks
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        echo "✓ Re-enabled FK checks\n\n";

        // Now run individual seeders
        echo "=== Phase 1: Master Data ===\n";
        $this->call('ImportProductsFromKiotViet');
        $this->call('ImportCustomersFromKiotViet');
        $this->call('ImportSuppliersFromKiotViet');

        echo "\n=== Phase 2: Transactions ===\n";
        $this->call('ImportInvoicesFromKiotViet');
        $this->call('ImportPurchaseOrdersFromKiotViet');

        echo "\n=== Phase 3: Returns ===\n";
        $this->call('ImportReturnsFromKiotViet');

        echo "\n=== Phase 4: Financial ===\n";
        $this->call('ImportCashFromKiotViet');

        echo "\n=== Import Complete ===\n";
    }
}
