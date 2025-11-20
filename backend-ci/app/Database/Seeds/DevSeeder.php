<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class DevSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now();
        $this->db->table('roles')->ignore(true)->insert([
            'id' => 1,
            'name' => 'super-admin',
            'guard_name' => 'api',
            'description' => 'Super Admin',
            'is_system' => 1,
            'created_at' => $now,
        ]);

        $this->db->table('users')->ignore(true)->insert([
            'id' => 1,
            'username' => 'devadmin',
            'email' => 'admin@lanocrm.local',
            'password' => '$2y$10$TB.SwSOiQgQHFnnHR2H7wexLTwnOC90/gQK32nNLO4DHXyVPhrtGm',
            'full_name' => 'Dev Admin',
            'branch_id' => 1,
            'status' => 'active',
            'created_at' => $now,
        ]);

        $this->db->table('model_has_roles')->ignore(true)->insert([
            'role_id' => 1,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);

        $this->db->table('permissions')->ignore(true)->insertBatch([
            ['id'=>1,'name'=>'users.view','display_name'=>'View Users','module'=>'users','module_group'=>'admin','guard_name'=>'api','created_at'=>$now],
            ['id'=>2,'name'=>'users.create','display_name'=>'Create Users','module'=>'users','module_group'=>'admin','guard_name'=>'api','created_at'=>$now],
        ]);

        $this->db->table('role_has_permissions')->ignore(true)->insertBatch([
            ['permission_id'=>1,'role_id'=>1],
            ['permission_id'=>2,'role_id'=>1],
        ]);
    }
}
