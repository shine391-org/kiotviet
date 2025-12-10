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
                'password' => '$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G', // 123aA@hai
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

        // Branches tối thiểu
        $this->db->table('branches')->ignore(true)->insertBatch([
            ['id' => 1, 'name' => 'Chi nhánh Hà Nội', 'code' => 'HN01', 'status' => 'active', 'created_at' => $now],
            ['id' => 2, 'name' => 'Chi nhánh HCM',    'code' => 'HCM01', 'status' => 'active', 'created_at' => $now],
        ]);

        // Company mặc định để các test/feature có company_id hợp lệ
        $this->db->table('companies')->ignore(true)->insert([
            'id' => 1,
            'code' => 'COMP-DEFAULT',
            'name' => 'Default Company',
            'is_default' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Quyền công ty mẫu: user 1 có toàn quyền admin trên công ty mặc định
        $this->db->table('company_permissions')->ignore(true)->insert([
            'company_id' => 1,
            'user_id' => 1,
            'role_name' => 'admin',
            'permissions' => json_encode(['admin', 'read', 'write', 'share']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Categories (gọi CategorySeeder để tạo đầy đủ categories cho ProductSeeder)
        $this->call('CategorySeeder');

        // Thuộc tính + options mẫu
        $this->db->table('product_attributes')->ignore(true)->insertBatch([
            ['id'=> 201, 'name'=>'Màu sắc', 'slug'=>'mau-sac', 'attribute_key'=>'color', 'type'=>'select', 'is_required'=>0, 'is_filterable'=>1, 'sort_order'=>1, 'status'=>'active', 'is_visible'=>1, 'created_at'=>$now],
            ['id'=> 202, 'name'=>'Kích thước', 'slug'=>'size', 'attribute_key'=>'size', 'type'=>'select', 'is_required'=>0, 'is_filterable'=>1, 'sort_order'=>2, 'status'=>'active', 'is_visible'=>1, 'created_at'=>$now],
        ]);

        $options = [
            ['id'=>301,'attribute_id'=>201,'option_name'=>'Đen','color_code'=>'#000000','sort_order'=>1,'status'=>'active','created_at'=>$now],
            ['id'=>302,'attribute_id'=>201,'option_name'=>'Nâu','color_code'=>'#5b3a29','sort_order'=>2,'status'=>'active','created_at'=>$now],
            ['id'=>303,'attribute_id'=>202,'option_name'=>'M','sort_order'=>1,'status'=>'active','created_at'=>$now],
            ['id'=>304,'attribute_id'=>202,'option_name'=>'L','sort_order'=>2,'status'=>'active','created_at'=>$now],
        ];
        foreach ($options as $row) {
            $this->db->table('product_attribute_options')->ignore(true)->insert($row);
        }

        // Payment methods master data
        $this->call('PaymentMethodSeeder');

        // Note: ProductSeeder đã được loại bỏ khỏi đây
        // ProductVariantsDemoSeeder trong DemoSeeder là source of truth cho products
        // vì nó tạo đúng variant IDs (50101, 50102...) mà OrdersDemoSeeder cần
        // PriceListSeeder và InventoryStockSeeder được gọi sau ProductVariantsDemoSeeder
    }
}
