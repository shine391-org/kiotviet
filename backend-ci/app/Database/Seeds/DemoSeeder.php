<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Database\Seeds\DevSeeder;
use CodeIgniter\I18n\Time;

/**
 * DemoSeeder - Unified seeder for Development and Staging environments.
 *
 * @agent-seeder: Unified Demo Data
 * @agent-pattern: Ordered execution of Demo seeders + Staging users
 * @agent-reusable: HIGH
 */
class DemoSeeder extends Seeder
{
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

        // 2. Add Staging/Test Users (Unified Set)
        $this->seedTestUsers();

        // 3. Define execution order for dependencies
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

                $this->db->table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId
                ]);
            }
        }
    }
}
