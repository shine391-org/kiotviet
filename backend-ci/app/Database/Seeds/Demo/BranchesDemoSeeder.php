<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * BranchesDemoSeeder - Demo branches and warehouses for development
 * 
 * @agent-seeder: Demo branches
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Creates demo branches/warehouses for testing:
 * - Multiple branches in different locations
 * - Various warehouse types
 * - Realistic branch hierarchy
 * - Test data for multi-branch scenarios
 */
class BranchesDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo branches and warehouses...\n";
        
        $now = Time::now();

        // Additional demo branches (beyond 2 base branches in DevSeeder)
        $branches = [
            [
                'id' => 3,
                'name' => 'Chi nhánh Đà Nẵng',
                'code' => 'DN01',
                'status' => 'active',
            ],
            [
                'id' => 4,
                'name' => 'Chi nhánh Cần Thơ',
                'code' => 'CT01',
                'status' => 'active',
            ],
            [
                'id' => 5,
                'name' => 'Chi nhánh Hải Phòng',
                'code' => 'HP01',
                'status' => 'active',
            ],
            [
                'id' => 6,
                'name' => 'Chi nhánh Test (Inactive)',
                'code' => 'TEST01',
                'status' => 'inactive',
            ],
        ];

        foreach ($branches as $branch) {
            $branch['created_at'] = $now;
            $branch['updated_at'] = $now;
            $this->db->table('branches')->ignore(true)->insert($branch);
        }

        // Demo warehouses for each branch
        $warehouses = [
            // Hanoi warehouses
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
            // HCM warehouses
            [
                'id' => 3,
                'branch_id' => 2,
                'code' => 'WH-HCM-MAIN',
                'name' => 'Kho chính HCM',
                'status' => 'active',
            ],
            [
                'id' => 4,
                'branch_id' => 2,
                'code' => 'WH-HCM-RETAIL',
                'name' => 'Kho bán lẻ HCM',
                'status' => 'active',
            ],
            // Da Nang warehouse
            [
                'id' => 5,
                'branch_id' => 3,
                'code' => 'WH-DN-MAIN',
                'name' => 'Kho chính Đà Nẵng',
                'status' => 'active',
            ],
            // Can Tho warehouse
            [
                'id' => 6,
                'branch_id' => 4,
                'code' => 'WH-CT-MAIN',
                'name' => 'Kho chính Cần Thơ',
                'status' => 'active',
            ],
            // Hai Phong warehouse
            [
                'id' => 7,
                'branch_id' => 5,
                'code' => 'WH-HP-MAIN',
                'name' => 'Kho chính Hải Phòng',
                'status' => 'active',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            $warehouse['created_at'] = $now;
            $warehouse['updated_at'] = $now;
            $this->db->table('warehouses')->ignore(true)->insert($warehouse);
        }

        echo "      ✓ Created " . count($branches) . " demo branches\n";
        echo "      ✓ Created " . count($warehouses) . " demo warehouses\n";
        echo "\n";
        echo "      Branch Structure:\n";
        echo "      1. Hà Nội (HN01) - 2 warehouses\n";
        echo "      2. HCM (HCM01) - 2 warehouses\n";
        echo "      3. Đà Nẵng (DN01) - 1 warehouse\n";
        echo "      4. Cần Thơ (CT01) - 1 warehouse\n";
        echo "      5. Hải Phòng (HP01) - 1 warehouse\n";
        echo "      6. Test Branch (INACTIVE)\n";
    }
}