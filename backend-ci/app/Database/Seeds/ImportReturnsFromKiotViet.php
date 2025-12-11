<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import returns from KiotViet export.
 * Optimized version with batch insert.
 */
class ImportReturnsFromKiotViet extends Seeder
{
    public function run(): void
    {
        $jsonFile = APPPATH . 'Database/Seeds/Data/returns_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ Returns JSON file not found\n";
            return;
        }

        echo "→ Importing returns...\n";
        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) { echo "❌ Invalid JSON\n"; return; }

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $rows = [];
        $imported = 0;

        foreach ($data as $item) {
            // Skip if no return code
            if (empty($item['code'])) continue;

            $rows[] = [
                'return_number' => $item['code'],
                'order_id' => null, // Skip FK for now
                'customer_id' => null, // Skip FK for now
                'return_amount' => (float) ($item['total_amount'] ?? 0),
                'refund_amount' => (float) ($item['refund_amount'] ?? 0),
                'reason' => $item['reason'] ?? null,
                'notes' => $item['notes'] ?? null,
                'status' => 'completed',
                'created_at' => $item['created_at'] ?? $now,
                'updated_at' => $now,
            ];

            // Batch insert
            if (count($rows) >= $batchSize) {
                $this->db->table('returns')->insertBatch($rows);
                $imported += count($rows);
                echo "   Imported {$imported} returns...\n";
                $rows = [];
            }
        }

        // Insert remaining
        if (count($rows) > 0) {
            $this->db->table('returns')->insertBatch($rows);
            $imported += count($rows);
        }

        echo "✅ Imported {$imported} returns\n";
    }
}
