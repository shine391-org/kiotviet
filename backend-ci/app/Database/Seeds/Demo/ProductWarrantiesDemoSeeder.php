<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Demo seed for product warranties.
 *
 * @agent-seeder: Product Warranties
 * @agent-pattern: Insert batch
 * @agent-reusable: MEDIUM
 */
class ProductWarrantiesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!$this->db->tableExists('product_warranties')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $warranties = [];

        // Generate 20 warranties
        for ($i = 1; $i <= 20; $i++) {
            $warranties[] = [
                'product_id' => 510, // TSHIRT-PERF
                'order_id' => null,
                'customer_id' => null,
                'serial_number' => 'SN-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'warranty_code' => 'WAR-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year')),
                'status' => 'active',
                'note' => 'Demo warranty ' . $i,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Cleanup
        $codes = array_column($warranties, 'warranty_code');
        $this->db->table('product_warranties')->whereIn('warranty_code', $codes)->delete();

        // Insert
        $this->db->table('product_warranties')->ignore(true)->insertBatch($warranties);
    }
}
