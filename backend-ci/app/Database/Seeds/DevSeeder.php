<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class DevSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now();
        // Roles
        $this->db->table('roles')->ignore(true)->insertBatch([
            ['id' => 1, 'name' => 'super-admin', 'guard_name' => 'api', 'description' => 'Super Admin', 'is_system' => 1, 'created_at' => $now],
            ['id' => 2, 'name' => 'manager',     'guard_name' => 'api', 'description' => 'Quản lý',     'is_system' => 0, 'created_at' => $now],
            ['id' => 3, 'name' => 'viewer',      'guard_name' => 'api', 'description' => 'Xem chỉ đọc', 'is_system' => 0, 'created_at' => $now],
        ]);

        // Users
        $this->db->table('users')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'username' => 'devadmin',
                'email' => 'admin@lanocrm.local',
                'password' => '$2y$10$TB.SwSOiQgQHFnnHR2H7wexLTwnOC90/gQK32nNLO4DHXyVPhrtGm', // Admin@123
                'full_name' => 'Dev Admin',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
            ],
            [
                'id' => 2,
                'username' => 'manager1',
                'email' => 'manager@lanocrm.local',
                'password' => '$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2', // Manager@123
                'full_name' => 'Manager Test',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
            ],
            [
                'id' => 3,
                'username' => 'viewer1',
                'email' => 'viewer@lanocrm.local',
                'password' => '$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu', // Viewer@123
                'full_name' => 'Viewer Test',
                'branch_id' => 1,
                'status' => 'active',
                'created_at' => $now,
            ],
        ]);

        // Role mapping
        $this->db->table('model_has_roles')->ignore(true)->insertBatch([
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1],
            ['role_id' => 2, 'model_type' => 'App\\Models\\User', 'model_id' => 2],
            ['role_id' => 3, 'model_type' => 'App\\Models\\User', 'model_id' => 3],
        ]);

        // Permissions
        $this->db->table('permissions')->ignore(true)->insertBatch([
            ['id'=>1,'name'=>'users.view','display_name'=>'Xem người dùng','module'=>'users','module_group'=>'admin','guard_name'=>'api','created_at'=>$now],
            ['id'=>2,'name'=>'users.manage','display_name'=>'Quản lý người dùng','module'=>'users','module_group'=>'admin','guard_name'=>'api','created_at'=>$now],
            ['id'=>3,'name'=>'products.view','display_name'=>'Xem sản phẩm','module'=>'products','module_group'=>'catalog','guard_name'=>'api','created_at'=>$now],
            ['id'=>4,'name'=>'products.manage','display_name'=>'Quản lý sản phẩm','module'=>'products','module_group'=>'catalog','guard_name'=>'api','created_at'=>$now],
        ]);

        // Role-permission links
        $this->db->table('role_has_permissions')->ignore(true)->insertBatch([
            // super-admin full
            ['permission_id'=>1,'role_id'=>1],
            ['permission_id'=>2,'role_id'=>1],
            ['permission_id'=>3,'role_id'=>1],
            ['permission_id'=>4,'role_id'=>1],
            // manager
            ['permission_id'=>1,'role_id'=>2],
            ['permission_id'=>3,'role_id'=>2],
            ['permission_id'=>4,'role_id'=>2],
            // viewer
            ['permission_id'=>1,'role_id'=>3],
            ['permission_id'=>3,'role_id'=>3],
        ]);
    }
}
