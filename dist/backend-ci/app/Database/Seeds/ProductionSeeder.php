<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * ProductionSeeder - Essential master data only for production environment
 * 
 * @agent-seeder: Production master data
 * @agent-pattern: Minimal essential data only
 * @agent-reusable: HIGH
 * 
 * Contains:
 * - System roles (super-admin only)
 * - Default company
 * - Essential permissions
 * - Payment methods
 * - Base product categories
 * - Base product attributes
 * 
 * Does NOT contain:
 * - Demo users (create manually)
 * - Demo products
 * - Demo transactions
 * - Test data
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'development') {
            echo "⚠️  ProductionSeeder skipped in development environment\n";
            echo "   Use DevDemoSeeder instead for development\n";
            return;
        }

        echo "🚀 Seeding production master data...\n";
        
        $now = Time::now();

        // 1. System Roles (minimal)
        echo "   → Roles...\n";
        $this->db->table('roles')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'name' => 'super-admin',
                'guard_name' => 'api',
                'description' => 'Super Administrator',
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'admin',
                'guard_name' => 'api',
                'description' => 'Administrator',
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'manager',
                'guard_name' => 'api',
                'description' => 'Manager',
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'staff',
                'guard_name' => 'api',
                'description' => 'Staff',
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 2. Essential Permissions
        echo "   → Permissions...\n";
        $permissions = [
            // User management
            ['name' => 'users.view', 'display_name' => 'View Users', 'module' => 'users', 'module_group' => 'admin'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'module' => 'users', 'module_group' => 'admin'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'module' => 'users', 'module_group' => 'admin'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'module' => 'users', 'module_group' => 'admin'],
            
            // Product management
            ['name' => 'products.view', 'display_name' => 'View Products', 'module' => 'products', 'module_group' => 'catalog'],
            ['name' => 'products.create', 'display_name' => 'Create Products', 'module' => 'products', 'module_group' => 'catalog'],
            ['name' => 'products.edit', 'display_name' => 'Edit Products', 'module' => 'products', 'module_group' => 'catalog'],
            ['name' => 'products.delete', 'display_name' => 'Delete Products', 'module' => 'products', 'module_group' => 'catalog'],
            
            // Order management
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'module' => 'orders', 'module_group' => 'sales'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'module' => 'orders', 'module_group' => 'sales'],
            ['name' => 'orders.edit', 'display_name' => 'Edit Orders', 'module' => 'orders', 'module_group' => 'sales'],
            ['name' => 'orders.delete', 'display_name' => 'Delete Orders', 'module' => 'orders', 'module_group' => 'sales'],
            
            // Invoice management
            ['name' => 'invoices.view', 'display_name' => 'View Invoices', 'module' => 'invoices', 'module_group' => 'accounting'],
            ['name' => 'invoices.create', 'display_name' => 'Create Invoices', 'module' => 'invoices', 'module_group' => 'accounting'],
            ['name' => 'invoices.edit', 'display_name' => 'Edit Invoices', 'module' => 'invoices', 'module_group' => 'accounting'],
            ['name' => 'invoices.delete', 'display_name' => 'Delete Invoices', 'module' => 'invoices', 'module_group' => 'accounting'],
        ];

        foreach ($permissions as $idx => $perm) {
            $this->db->table('permissions')->ignore(true)->insert([
                'id' => $idx + 1,
                'name' => $perm['name'],
                'display_name' => $perm['display_name'],
                'module' => $perm['module'],
                'module_group' => $perm['module_group'],
                'guard_name' => 'api',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Default Company
        echo "   → Default company...\n";
        $this->db->table('companies')->ignore(true)->insert([
            'id' => 1,
            'code' => 'MAIN',
            'name' => 'Main Company',
            'is_default' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. Default Branch
        echo "   → Default branch...\n";
        $this->db->table('branches')->ignore(true)->insert([
            'id' => 1,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 5. Payment Methods
        echo "   → Payment methods...\n";
        $this->call('PaymentMethodSeeder');

        // 6. Base Product Categories
        echo "   → Product categories...\n";
        $this->db->table('product_categories')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'product_id' => 0,
                'parent_id' => null,
                'level' => 1,
                'is_variant_group' => 0,
                'code' => 'GENERAL',
                'name' => 'General',
                'slug' => 'general',
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 7. Base Product Attributes
        echo "   → Product attributes...\n";
        $this->db->table('product_attributes')->ignore(true)->insertBatch([
            [
                'id' => 1,
                'name' => 'Color',
                'slug' => 'color',
                'attribute_key' => 'color',
                'type' => 'select',
                'is_required' => 0,
                'is_filterable' => 1,
                'sort_order' => 1,
                'status' => 'active',
                'is_visible' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Size',
                'slug' => 'size',
                'attribute_key' => 'size',
                'type' => 'select',
                'is_required' => 0,
                'is_filterable' => 1,
                'sort_order' => 2,
                'status' => 'active',
                'is_visible' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        echo "✅ Production master data seeded successfully\n";
        echo "\n";
        echo "⚠️  IMPORTANT: Create your first admin user manually:\n";
        echo "   php spark db:seed CreateFirstAdminSeeder\n";
        echo "   (You need to create this seeder with your admin credentials)\n";
    }
}