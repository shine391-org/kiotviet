<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import suppliers from KiotViet export.
 */
class ImportSuppliersFromKiotViet extends Seeder
{
    public function run(): void
    {
        $jsonFile = APPPATH . 'Database/Seeds/Data/suppliers_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ Suppliers JSON file not found\n";
            return;
        }

        echo "→ Importing suppliers...\n";
        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) { echo "❌ Invalid JSON\n"; return; }

        $now = date('Y-m-d H:i:s');
        $imported = 0;
        foreach ($data as $item) {
            // Skip if no code or name (required fields)
            $code = $item['code'] ?? ('SUP-' . ($imported + 1));
            $name = $item['name'] ?? 'Unknown Supplier';
            $phone = $item['phone'] ?? '0000000000';
            
            $this->db->table('partners')->insert([
                'code' => $code,
                'name' => $name,
                'phone' => $phone,
                'email' => $item['email'] ?? null,
                'address' => $item['address'] ?? null,
                'tax_code' => $item['tax_code'] ?? null,
                'contact_person' => $item['contact_person'] ?? null,
                'debt_amount' => (float) ($item['debt_amount'] ?? 0),
                'status' => 'active',
                'type' => 'supplier',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $imported++;
        }
        echo "✅ Imported {$imported} suppliers\n";
    }
}
