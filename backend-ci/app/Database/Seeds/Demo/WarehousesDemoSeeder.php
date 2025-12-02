<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * WarehousesDemoSeeder - Demo warehouses with inventory data
 * 
 * @agent-seeder: Demo warehouses
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class WarehousesDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo warehouses...\n";
        
        $now = Time::now();

        // Warehouses (if not created by BranchesDemoSeeder)
        $warehouses = [
            [
                'id' => 1,
                'branch_id' => 1,
                'code' => 'WH-HN-MAIN',
                'name' => 'Kho chính Hà Nội',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'branch_id' => 1,
                'code' => 'WH-HN-RETAIL',
                'name' => 'Kho bán lẻ Hà Nội',
                'status' => 'active',
            ],
            [
                'id' => 3,
                'branch_id' => 2,
                'code' => 'WH-HCM-MAIN',
                'name' => 'Kho chính HCM',
                'status' => 'active',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            $warehouse['created_at'] = $now;
            $warehouse['updated_at'] = $now;
            $this->db->table('warehouses')->ignore(true)->insert($warehouse);
        }

        echo "      ✓ Created " . count($warehouses) . " demo warehouses\n";
    }
}