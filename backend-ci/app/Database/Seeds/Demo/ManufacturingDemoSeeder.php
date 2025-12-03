<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * ManufacturingDemoSeeder - Demo Manufacturing data
 * 
 * @agent-seeder: Demo Manufacturing
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class ManufacturingDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo Manufacturing data (BOM)...\n";

        // Skip if manufacturing tables are missing
        $requiredTables = ['bom', 'bom_items'];
        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                echo "      ⚠ Skipped Manufacturing demo (missing table: {$table})\n";
                return;
            }
        }
        
        $now = Time::now();

        // 1. Bill of Materials (BOM)
        // Assuming Product ID 1 is a manufactured product (e.g., "Combo Túi + Ví")
        // And Product ID 2 (Túi) and 3 (Ví) are components
        
        $boms = [
            [
                'id' => 1,
                'product_id' => 1, // The finished good
                'name' => 'BOM for Combo Set',
                'version' => '1.0',
                'is_active' => 1,
                'is_default' => 1,
            ]
        ];

        foreach ($boms as $bom) {
            $bom['created_at'] = $now;
            $bom['updated_at'] = $now;
            $this->db->table('bom')->ignore(true)->insert($bom);
        }

        // 2. BOM Items
        $bomItems = [
            [
                'bom_id' => 1,
                'component_product_id' => 2, // Component 1
                'quantity' => 1,
                'unit' => 'pcs',
                'wastage_percent' => 0,
            ],
            [
                'bom_id' => 1,
                'component_product_id' => 3, // Component 2
                'quantity' => 1,
                'unit' => 'pcs',
                'wastage_percent' => 0,
            ],
        ];

        foreach ($bomItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('bom_items')->ignore(true)->insert($item);
        }

        echo "      ✓ Created Manufacturing data\n";
    }
}
