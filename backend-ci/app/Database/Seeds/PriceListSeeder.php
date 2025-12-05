<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        // 1. Clean old data
        $this->db->table('price_list_items')->truncate();
        $this->db->table('price_lists')->truncate();

        // 2. Create General Price List (ID = 1, System, Fixed)
        $general = [
            'id'              => 1,
            'name'            => 'Bảng giá chung',
            'type'            => 'default',
            'is_active'       => 1,
            'is_system'       => 1, // System list - Cannot delete
            'start_date'      => date('Y-m-d'),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $this->db->table('price_lists')->insert($general);
        echo "Created 'Bảng giá chung' (ID=1, System).\n";

        // 3. Create Custom Price List "Bảng giá VIP" (ID = 2, Initially Empty)
        // User requested "Empty by default", so we do NOT insert items here.
        // User can test "Add Product" feature manually.
        $sale = [
            'id'              => 2,
            'name'            => 'Bảng giá VIP (Demo)',
            'type'            => 'normal',
            'is_active'       => 1,
            'is_system'       => 0,
            'start_date'      => date('Y-m-d'),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $this->db->table('price_lists')->insert($sale);
        echo "Created 'Bảng giá VIP' (ID=2) [Empty Items].\n";
        
        $this->db->enableForeignKeyChecks();
    }
}
