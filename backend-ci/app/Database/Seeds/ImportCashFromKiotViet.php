<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import cash transactions from KiotViet export.
 * Run: php spark db:seed ImportCashFromKiotViet
 */
class ImportCashFromKiotViet extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "⚠️  ImportCashFromKiotViet skipped in production\n";
            return;
        }

        if (! $this->db->tableExists('cash_transactions')) {
            echo "❌ Table cash_transactions does not exist\n";
            return;
        }

        $jsonFile = APPPATH . 'Database/Seeds/Data/cash_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ JSON file not found: {$jsonFile}\n";
            echo "Run: NODE_PATH=./node_modules node scripts/convert-cash-export.js\n";
            return;
        }

        echo "→ Importing cash transactions from KiotViet export...\n";

        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data || ! is_array($data)) {
            echo "❌ Invalid JSON data\n";
            return;
        }

        // Master seeder handles truncate
        echo "   Processing " . count($data) . " transactions...\n";

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $total = count($data);
        $imported = 0;

        foreach (array_chunk($data, $batchSize) as $batch) {
            $rows = [];
            foreach ($batch as $item) {
                $rows[] = [
                    'type' => $item['type'] ?? 'RECEIPT',
                    'amount' => (float) ($item['amount'] ?? 0),
                    'category' => $item['category'] ?? 'other_income',
                    'payment_method' => $item['payment_method'] ?? 'cash',
                    'status' => $item['status'] ?? 'approved',
                    'description' => $item['description'] ?? null,
                    'reference_type' => $item['reference_type'] ?? 'manual',
                    'reference_code' => $item['reference_code'] ?? null,
                    'branch_id' => 1, // Default to branch 1 to avoid FK errors
                    'created_by' => (int) ($item['created_by'] ?? 1),
                    'created_by_name' => $item['created_by_name'] ?? 'import',
                    'staff_name' => $item['staff_name'] ?? null,
                    'payer_code' => $item['payer_code'] ?? null,
                    'payer_name' => $item['payer_name'] ?? null,
                    'payer_phone' => $item['payer_phone'] ?? null,
                    'payer_address' => $item['payer_address'] ?? null,
                    'transfer_note' => $item['transfer_note'] ?? null,
                    'transaction_date' => $item['transaction_date'] ?? date('Y-m-d'),
                    'note' => $item['note'] ?? null,
                    'created_at' => $item['created_at'] ?? $now,
                    'updated_at' => $now,
                ];
            }
            
            $this->db->table('cash_transactions')->insertBatch($rows);
            $imported += count($rows);
            echo "   Imported {$imported}/{$total} rows\n";
        }

        echo "✅ Imported {$imported} cash transactions\n";
        
        // Show summary
        $summary = $this->db->query("
            SELECT reference_type, COUNT(*) as cnt 
            FROM cash_transactions 
            WHERE deleted_at IS NULL 
            GROUP BY reference_type
        ")->getResultArray();
        
        echo "\n📊 Summary by reference_type:\n";
        foreach ($summary as $row) {
            echo "   - {$row['reference_type']}: {$row['cnt']}\n";
        }
    }
}
