<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * StagingSeeder - Production-like test data for staging environment
 * 
 * @agent-seeder: Staging test data
 * @agent-pattern: Production-like realistic data
 * @agent-reusable: HIGH
 * 
 * Contains:
 * - Production master data (via ProductionSeeder)
 * - Test users with various roles
 * - Sample products (realistic but limited)
 * - Sample customers
 * - Sample orders (for testing workflows)
 * 
 * Purpose:
 * - Test production deployment
 * - Verify workflows before production
 * - Performance testing with realistic data
 * - Training environment
 */
class StagingSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "⚠️  StagingSeeder should not run in production!\n";
            return;
        }

        echo "🧪 Seeding staging environment...\n";
        
        $now = Time::now();

        // 1. First, seed production master data
        echo "   → Production master data...\n";
        $this->call('ProductionSeeder');

        // 2. Test Users (realistic roles)
        echo "   → Test users...\n";
        $this->db->table('users')->ignore(true)->insertBatch([
            [
                'id' => 1,
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
                'id' => 2,
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
                'id' => 3,
                'username' => 'staff.staging',
                'email' => 'staff@staging.lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Staging Staff',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 3. Role assignments
        echo "   → User roles...\n";
        $this->db->table('model_has_roles')->ignore(true)->insertBatch([
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1], // super-admin
            ['role_id' => 3, 'model_type' => 'App\\Models\\User', 'model_id' => 2], // manager
            ['role_id' => 4, 'model_type' => 'App\\Models\\User', 'model_id' => 3], // staff
        ]);

        // 4. Company permissions
        echo "   → Company permissions...\n";
        $this->db->table('company_permissions')->ignore(true)->insertBatch([
            [
                'company_id' => 1,
                'user_id' => 1,
                'role_name' => 'admin',
                'permissions' => json_encode(['admin', 'read', 'write', 'share']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'user_id' => 2,
                'role_name' => 'manager',
                'permissions' => json_encode(['read', 'write']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'user_id' => 3,
                'role_name' => 'staff',
                'permissions' => json_encode(['read']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 5. Additional branches for testing
        echo "   → Additional branches...\n";
        $this->db->table('branches')->ignore(true)->insertBatch([
            [
                'id' => 2,
                'name' => 'Branch North',
                'code' => 'NORTH',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Branch South',
                'code' => 'SOUTH',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 6. Sample products (limited, realistic)
        echo "   → Sample products...\n";
        $this->call('ProductSeeder');

        // 7. Sample customers
        echo "   → Sample customers...\n";
        $this->db->table('customers')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'code' => 'CUST-001',
                'name' => 'Test Customer A',
                'email' => 'customer.a@test.local',
                'phone' => '0901234567',
                'type' => 'individual',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'code' => 'CUST-002',
                'name' => 'Test Company B',
                'email' => 'company.b@test.local',
                'phone' => '0907654321',
                'type' => 'company',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 8. Sample price lists
        echo "   → Sample price lists...\n";
        $this->db->table('price_lists')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'name' => 'Standard Price',
                'code' => 'STANDARD',
                'type' => 'selling',
                'is_default' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Wholesale Price',
                'code' => 'WHOLESALE',
                'type' => 'selling',
                'is_default' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        echo "✅ Staging environment seeded successfully\n";
        echo "\n";
        echo "📋 Test Accounts:\n";
        echo "   Admin:   admin.staging / 123aA@hai\n";
        echo "   Manager: manager.staging / Manager@123\n";
        echo "   Staff:   staff.staging / Viewer@123\n";
        echo "\n";
        echo "💡 Next steps:\n";
        echo "   1. Test user authentication\n";
        echo "   2. Test product management\n";
        echo "   3. Test order workflows\n";
        echo "   4. Test invoice generation\n";
        echo "   5. Verify all features work as expected\n";
    }
}