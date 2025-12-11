<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import products from KiotViet export.
 * Run: php spark db:seed ImportProductsFromKiotViet
 */
class ImportProductsFromKiotViet extends Seeder
{
    public function run(): void
    {
        $jsonFile = APPPATH . 'Database/Seeds/Data/products_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ Products JSON file not found\n";
            return;
        }

        echo "→ Importing products...\n";
        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) { echo "❌ Invalid JSON\n"; return; }

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $total = count($data);
        $imported = 0;

        foreach (array_chunk($data, $batchSize) as $batch) {
            $rows = [];
            foreach ($batch as $item) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $item['name'] ?? 'product'));
                $rows[] = [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'slug' => $slug . '-' . uniqid(),
                    'barcode' => $item['barcode'],
                    'unit' => $item['unit'],
                    'purchase_price' => $item['cost_price'] ?? 0,
                    'selling_price' => $item['selling_price'] ?? 0,
                    'weight' => $item['weight'],
                    'description' => $item['description'],
                    'status' => ($item['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'active' : 'inactive',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('products')->insertBatch($rows);
            $imported += count($rows);
        }
        echo "✅ Imported {$imported} products\n";
    }
}
