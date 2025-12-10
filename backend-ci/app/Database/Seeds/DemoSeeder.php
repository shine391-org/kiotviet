<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Database\Seeds\DevSeeder;
use App\Database\Seeds\MasterDataSeeder;
use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * DemoSeeder - Unified seeder for Development and Staging environments.
 *
 * @agent-seeder: Unified Demo Data
 * @agent-pattern: Ordered execution of Demo seeders + Staging users
 * @agent-reusable: HIGH
 */
class DemoSeeder extends Seeder
{
    /**
     * Override call to pass current db connection to child seeders.
     * This ensures -g tests flag works for all child seeders.
     */
    public function call(string $class): void
    {
        $class = trim($class);
        if ($class === '') {
            return;
        }

        // Load class if needed
        if (! str_contains($class, '\\')) {
            $path = APPPATH . 'Database/Seeds/' . str_replace('.php', '', $class) . '.php';
            if (is_file($path)) {
                require_once $path;
                $class = APP_NAMESPACE . '\Database\Seeds\\' . $class;
            }
        }

        if (! class_exists($class)) {
            echo "⚠️  Seeder not found: {$class}\n";
            return;
        }

        // Pass current db connection to child seeder
        $seeder = new $class(new Database(), $this->db);
        $seeder->run();

        if (is_cli()) {
            \CodeIgniter\CLI\CLI::write("Seeded: {$class}", 'green');
        }
    }

    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "⚠️  DemoSeeder skipped in PRODUCTION environment!\n";
            return;
        }

        echo "🚀 Starting Unified Demo Seeder...\n";

        // 1. Base dev seed (roles/users/master data)
        if (class_exists(DevSeeder::class)) {
            echo "Seeding base dev data: " . DevSeeder::class . "\n";
            $this->call(DevSeeder::class);
        }

        // 1b. Master data mới
        if (class_exists(MasterDataSeeder::class)) {
            $this->call(MasterDataSeeder::class);
        }

        // 2. Add Staging/Test Users (Unified Set)
        $this->seedTestUsers();

        // 3. Define execution order for dependencies
        // Order matters! Users -> Master Data -> Transactions
        $orderedSeeders = [
            // Core Data
            'UsersDemoSeeder',      // More users
            'BranchesDemoSeeder',   // Branches & Warehouses
            'WarehousesDemoSeeder', // More warehouses
            'TaxTemplatesDemoSeeder', // VAT templates
            'SuppliersDemoSeeder',  // Suppliers
            'CustomersDemoSeeder',  // Customers
            'CustomerDetailDemoSeeder', // Customer addresses and debt transactions
            'HRDemoSeeder',         // Employees (needs Users)
            
            // Product Data
            'ProductVariantsDemoSeeder', // Variants
            // PriceListSeeder gọi ở đây (sau ProductVariantsDemoSeeder) để tránh FK cascade delete
            'ProductWarrantiesDemoSeeder', // Warranties
            'ManufacturingDemoSeeder',   // BOM (needs Products)

            // Transaction Data
            'OrdersDemoSeeder',            // Orders (needs Customers, Products)
            'DeliveryNotesDemoSeeder',     // Delivery Notes (needs Orders)
            'InvoicesDemoSeeder',          // Invoices (needs Orders)
            'ReturnsDemoSeeder',           // Returns (needs Orders)
            'StockMovementsDemoSeeder',    // Stock movements (needs products/warehouses)
            'StockTransfersDemoSeeder',    // Stock transfers (needs branches/products)
            // 'StockLedgersDemoSeeder',      // Stock ledgers (needs orders/deliveries/returns)
            'PurchaseOrdersDemoSeeder',    // POs (needs Suppliers, Products)
            'CashTransactionsDemoSeeder',  // Cash linked to orders/returns
            
            // Other Modules
            'CRMDemoSeeder',               // CRM (needs Customers, Users)
            'AccountingDemoSeeder',        // Accounting (needs Users)
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
                
                // Gọi PriceListSeeder sau ProductVariantsDemoSeeder (tránh FK cascade delete)
                if ($seederName === 'ProductVariantsDemoSeeder') {
                    $this->call(\App\Database\Seeds\PriceListSeeder::class);
                    // Gọi InventoryStockSeeder sau để có stock cho POS
                    $this->call(\App\Database\Seeds\InventoryStockSeeder::class);
                }
            } else {
                echo "⚠️  Warning: Seeder {$seederName} not found\n";
            }
        }
    }

    private function seedTestUsers()
    {
        echo "   → Seeding unified test users (Staging/Dev)...\n";
        $now = Time::now();

        // These users were previously in StagingSeeder
        $users = [
            [
                'username' => 'admin.staging',
                'email' => 'admin@staging.lanocrm.local',
                'password' => '$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G', // 123aA@hai
                'full_name' => 'Staging Admin',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'manager.staging',
                'email' => 'manager@staging.lanocrm.local',
                'password' => '$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2', // Manager@123
                'full_name' => 'Staging Manager',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'staff.staging',
                'email' => 'staff@staging.lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Staging Staff',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        foreach ($users as $userData) {
            // Check if user exists
            $exists = $this->db->table('users')->where('email', $userData['email'])->countAllResults();
            if (!$exists) {
                $this->db->table('users')->insert($userData);
                $userId = $this->db->insertID();
                
                // Assign roles based on username
                $roleId = 4; // staff default
                if (strpos($userData['username'], 'admin') !== false) $roleId = 1;
                if (strpos($userData['username'], 'manager') !== false) $roleId = 3;

                $this->db->table('model_has_roles')->ignore(true)->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId
                ]);
            }
        }
    }
}
