<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import customers from KiotViet export.
 * Run: php spark db:seed ImportCustomersFromKiotViet
 */
class ImportCustomersFromKiotViet extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "⚠️  ImportCustomersFromKiotViet skipped in production\n";
            return;
        }

        if (! $this->db->tableExists('customers')) {
            echo "❌ Table customers does not exist\n";
            return;
        }

        $jsonFile = APPPATH . 'Database/Seeds/Data/customers_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ JSON file not found: {$jsonFile}\n";
            echo "Run: NODE_PATH=./node_modules node scripts/convert-customer-export.js\n";
            return;
        }

        echo "→ Importing customers from KiotViet export...\n";

        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data || ! is_array($data)) {
            echo "❌ Invalid JSON data\n";
            return;
        }

        // Master seeder handles truncate
        echo "   Processing " . count($data) . " customers...\n";

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $total = count($data);
        $imported = 0;

        foreach (array_chunk($data, $batchSize) as $batch) {
            $rows = [];
            foreach ($batch as $item) {
                $rows[] = [
                    'code' => $item['code'] ?? null,
                    'name' => $item['name'] ?? null,
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'address' => $item['address'] ?? null,
                    'ward' => $item['ward'] ?? null,
                    'district' => $item['district'] ?? null,
                    'company_name' => $item['company_name'] ?? null,
                    'tax_code' => $item['tax_code'] ?? null,
                    'birthday' => $item['birthday'] ?? null,
                    'gender' => $item['gender'] ?? null,
                    'facebook' => $item['facebook'] ?? null,
                    'notes' => $item['note'] ?? null,
                    'customer_type' => $item['customer_type'] ?? 'INDIVIDUAL',
                    'current_debt' => (float) ($item['current_debt'] ?? 0),
                    'total_sales' => (float) ($item['total_sales'] ?? 0),
                    'total_sales_net' => (float) ($item['total_sales_net'] ?? 0),
                    'status' => ($item['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'active' : 'inactive',
                    'created_at' => $item['created_at'] ?? $now,
                    'updated_at' => $now,
                ];
            }
            
            $this->db->table('customers')->insertBatch($rows);
            $imported += count($rows);
            echo "   Imported {$imported}/{$total} customers\n";
        }

        echo "✅ Imported {$imported} customers\n";
        
        // Verify KH002150
        $verify = $this->db->table('customers')
            ->where('code', 'KH002150')
            ->get()
            ->getRowArray();
        
        if ($verify) {
            echo "\n✓ Verified KH002150: {$verify['name']} - {$verify['phone']}\n";
        } else {
            echo "\n✗ KH002150 not found after import!\n";
        }
    }
}
