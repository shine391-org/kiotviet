<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import purchase orders from KiotViet export.
 */
class ImportPurchaseOrdersFromKiotViet extends Seeder
{
    public function run(): void
    {
        $jsonFile = APPPATH . 'Database/Seeds/Data/purchase_orders_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ Purchase Orders JSON file not found\n";
            return;
        }

        echo "→ Importing purchase orders...\n";
        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) { echo "❌ Invalid JSON\n"; return; }

        // Build supplier code → id map
        $supplierMap = [];
        $suppliers = $this->db->table('partners')->select('id, code')->where('type', 'supplier')->get()->getResultArray();
        foreach ($suppliers as $s) {
            if ($s['code']) $supplierMap[$s['code']] = $s['id'];
        }

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $imported = 0;

        foreach (array_chunk($data, $batchSize) as $batch) {
            $rows = [];
            foreach ($batch as $item) {
                $supplierId = $supplierMap[$item['supplier_code']] ?? null;
                $rows[] = [
                    'code' => $item['code'],
                    'partner_id' => $supplierId,
                    'branch_id' => 1,
                    'total' => $item['total'],
                    'paid_amount' => $item['paid_amount'],
                    'status' => 'completed',
                    'notes' => $item['notes'],
                    'created_by' => 1,
                    'created_at' => $item['created_at'] ?? $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('purchase_orders')->insertBatch($rows);
            $imported += count($rows);
        }
        echo "✅ Imported {$imported} purchase orders\n";
    }
}
