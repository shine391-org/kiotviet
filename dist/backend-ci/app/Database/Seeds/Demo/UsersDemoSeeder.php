<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * UsersDemoSeeder - Demo users with various roles for development
 * 
 * @agent-seeder: Demo users
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Creates demo users for testing:
 * - Various roles (admin, manager, staff, viewer)
 * - Different branches
 * - Different permissions
 * - Easy-to-remember credentials
 */
class UsersDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo users...\n";
        
        $now = Time::now();

        // Demo users with various roles
        $users = [
            [
                'id' => 10,
                'username' => 'demo.admin',
                'email' => 'demo.admin@lanocrm.local',
                'password' => '$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G', // 123aA@hai
                'full_name' => 'Demo Admin User',
                'branch_id' => 1,
                'status' => 'active',
                'role_id' => 1, // super-admin
            ],
            [
                'id' => 11,
                'username' => 'demo.manager.hn',
                'email' => 'manager.hn@lanocrm.local',
                'password' => '$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2', // Manager@123
                'full_name' => 'Demo Manager Hanoi',
                'branch_id' => 1,
                'status' => 'active',
                'role_id' => 2, // manager
            ],
            [
                'id' => 12,
                'username' => 'demo.manager.hcm',
                'email' => 'manager.hcm@lanocrm.local',
                'password' => '$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2', // Manager@123
                'full_name' => 'Demo Manager HCM',
                'branch_id' => 2,
                'status' => 'active',
                'role_id' => 2, // manager
            ],
            [
                'id' => 13,
                'username' => 'demo.staff1',
                'email' => 'staff1@lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Demo Staff 1',
                'branch_id' => 1,
                'status' => 'active',
                'role_id' => 3, // viewer
            ],
            [
                'id' => 14,
                'username' => 'demo.staff2',
                'email' => 'staff2@lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Demo Staff 2',
                'branch_id' => 2,
                'status' => 'active',
                'role_id' => 3, // viewer
            ],
            [
                'id' => 15,
                'username' => 'demo.inactive',
                'email' => 'inactive@lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Demo Inactive User',
                'branch_id' => 1,
                'status' => 'inactive',
                'role_id' => 3, // viewer
            ],
        ];

        foreach ($users as $user) {
            $roleId = $user['role_id'];
            unset($user['role_id']);
            
            $user['created_at'] = $now;
            $user['updated_at'] = $now;
            
            $this->db->table('users')->ignore(true)->insert($user);
            
            // Assign role
            $this->db->table('model_has_roles')->ignore(true)->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $user['id'],
            ]);
            
            // Assign company permissions
            $permissions = match($roleId) {
                1 => ['admin', 'read', 'write', 'share', 'delete'], // super-admin
                2 => ['read', 'write', 'share'], // manager
                3 => ['read'], // viewer
                default => ['read'],
            };
            
            $this->db->table('company_permissions')->ignore(true)->insert([
                'company_id' => 1,
                'user_id' => $user['id'],
                'role_name' => match($roleId) {
                    1 => 'admin',
                    2 => 'manager',
                    3 => 'viewer',
                    default => 'viewer',
                },
                'permissions' => json_encode($permissions),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "      ✓ Created " . count($users) . " demo users\n";
        echo "\n";
        echo "      Demo Accounts:\n";
        echo "      - demo.admin / 123aA@hai (Super Admin)\n";
        echo "      - demo.manager.hn / Manager@123 (Manager - Hanoi)\n";
        echo "      - demo.manager.hcm / Manager@123 (Manager - HCM)\n";
        echo "      - demo.staff1 / Viewer@123 (Staff - Hanoi)\n";
        echo "      - demo.staff2 / Viewer@123 (Staff - HCM)\n";
        echo "      - demo.inactive / Viewer@123 (Inactive)\n";
    }
}