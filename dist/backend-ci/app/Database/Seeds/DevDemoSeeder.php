<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Database\Seeds\DevSeeder;

/**
 * DevDemoSeeder - Automatically runs all seeders in Demo namespace for dev environment.
 *
 * @agent-seeder: Dev demo data
 * @agent-pattern: Ordered execution of Demo seeders
 * @agent-reusable: HIGH
 */
class DevDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT !== 'development') {
            echo "DevDemoSeeder skipped (ENV=" . ENVIRONMENT . ")\n";
            return;
        }

        // 1. Base dev seed (roles/users/master data)
        if (class_exists(DevSeeder::class)) {
            echo "Seeding base dev data: " . DevSeeder::class . "\n";
            $this->call(DevSeeder::class);
        }

        // 2. Define execution order for dependencies
        // Order matters! Users -> Master Data -> Transactions
        $orderedSeeders = [
            // Core Data
            'UsersDemoSeeder',      // More users
            'BranchesDemoSeeder',   // Branches & Warehouses
            'WarehousesDemoSeeder', // More warehouses
            'SuppliersDemoSeeder',  // Suppliers
            'CustomersDemoSeeder',  // Customers
            'HRDemoSeeder',         // Employees (needs Users)
            
            // Product Data
            'ProductVariantsDemoSeeder', // Variants
            'PriceListDemoSeeder',       // Price Lists
            'ManufacturingDemoSeeder',   // BOM (needs Products)

            // Transaction Data
            'OrdersDemoSeeder',          // Orders (needs Customers, Products)
            'InvoicesDemoSeeder',        // Invoices (needs Orders)
            'DeliveryNotesDemoSeeder',   // Delivery Notes (needs Orders)
            'ReturnsDemoSeeder',         // Returns (needs Orders)
            'PurchaseOrdersDemoSeeder',  // POs (needs Suppliers, Products)
            'StockMovementsDemoSeeder',  // Stock (needs Warehouses, Products)
            'CashTransactionsDemoSeeder',// Cash (needs Invoices/POs)
            
            // Other Modules
            'CRMDemoSeeder',             // CRM (needs Customers, Users)
            'AccountingDemoSeeder',      // Accounting (needs Users)
        ];

        $namespace = 'App\\Database\\Seeds\\Demo\\';

        foreach ($orderedSeeders as $seederName) {
            $class = $namespace . $seederName;
            
            // Try to load file if class doesn't exist
            if (! class_exists($class)) {
                $file = APPPATH . 'Database/Seeds/Demo/' . $seederName . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }

            if (class_exists($class)) {
                echo "Seeding demo: {$seederName}\n";
                $this->call($class);
            } else {
                echo "⚠️  Warning: Seeder {$seederName} not found\n";
            }
        }
    }
}
