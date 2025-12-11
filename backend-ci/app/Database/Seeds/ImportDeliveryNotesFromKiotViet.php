<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Import delivery notes (shipping data) from KiotViet invoices export.
 * Optimized version with batch insert.
 */
class ImportDeliveryNotesFromKiotViet extends Seeder
{
    public function run(): void
    {
        $jsonFile = APPPATH . 'Database/Seeds/Data/invoices_export.json';
        if (! file_exists($jsonFile)) {
            echo "❌ Invoices JSON file not found\n";
            return;
        }

        echo "→ Importing delivery notes (shipping data)...\n";
        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) { echo "❌ Invalid JSON\n"; return; }

        $now = date('Y-m-d H:i:s');
        $batchSize = 500;
        $rows = [];
        $imported = 0;
        $skipped = 0;
        $seen = []; // Track seen tracking codes to avoid duplicates

        foreach ($data as $item) {
            // Skip if no tracking code
            $tracking = trim($item['tracking_code'] ?? '');
            if (empty($tracking)) {
                $skipped++;
                continue;
            }

            // Skip duplicate tracking codes
            if (isset($seen[$tracking])) {
                $skipped++;
                continue;
            }
            $seen[$tracking] = true;

            // Generate unique delivery number
            $deliveryNumber = 'DN-' . str_replace(' ', '', $tracking);

            // Map delivery status
            $status = 'draft';
            $deliveryStatus = $item['delivery_status'] ?? '';
            if (str_contains($deliveryStatus, 'thành công') || str_contains($deliveryStatus, 'Hoàn thành')) {
                $status = 'delivered';
            } elseif (str_contains($deliveryStatus, 'Đang giao')) {
                $status = 'shipping';
            } elseif (str_contains($deliveryStatus, 'Đã lấy')) {
                $status = 'picked_up';
            } elseif (str_contains($deliveryStatus, 'Chờ')) {
                $status = 'pending';
            } elseif (str_contains($deliveryStatus, 'Hủy') || str_contains($deliveryStatus, 'Trả')) {
                $status = 'cancelled';
            }

            $rows[] = [
                'delivery_number' => $deliveryNumber,
                'order_id' => null, // Skip FK for now
                'customer_id' => null, // Skip FK for now
                'branch_id' => 1,
                'tracking_number' => $tracking,
                'carrier' => $item['delivery_partner'] ?? null,
                'status' => $status,
                'notes' => substr($item['delivery_notes'] ?? '', 0, 500), // Truncate long notes
                'delivered_at' => $item['delivery_time'] ?? null,
                'created_at' => $item['created_at'] ?? $now,
                'updated_at' => $now,
            ];

            // Batch insert
            if (count($rows) >= $batchSize) {
                $this->db->table('delivery_notes')->insertBatch($rows);
                $imported += count($rows);
                echo "   Imported {$imported} delivery notes...\n";
                $rows = [];
            }
        }

        // Insert remaining
        if (count($rows) > 0) {
            $this->db->table('delivery_notes')->insertBatch($rows);
            $imported += count($rows);
        }

        echo "✅ Imported {$imported} delivery notes (skipped {$skipped} without tracking or duplicates)\n";
    }
}
