<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

class StockTransfersDemoSeeder extends Seeder
{
    public function run()
    {
        // Cleanup existing demo data
        $demoCodes = ['TRF250001', 'TRF250002', 'TRF250003', 'TRF250004', 'TRF250005'];
        $existing = $this->db->table('stock_transfers')
            ->select('id')
            ->whereIn('code', $demoCodes)
            ->get()
            ->getResultArray();
        
        if (! empty($existing)) {
            $ids = array_column($existing, 'id');
            $this->db->table('stock_transfer_items')->whereIn('transfer_id', $ids)->delete();
            $this->db->table('stock_transfers')->whereIn('id', $ids)->delete();
        }
        // Get branches
        $branches = $this->db->table('branches')->get()->getResultArray();
        if (count($branches) < 2) {
            echo "Need at least 2 branches for stock transfers.\n";
            return;
        }

        // Get products
        $products = $this->db->table('products')
            ->limit(10)
            ->get()
            ->getResultArray();
        if (empty($products)) {
            echo "No products found.\n";
            return;
        }

        // Get users
        $users = $this->db->table('users')->limit(3)->get()->getResultArray();
        $userId = $users[0]['id'] ?? 1;

        $hcmBranch = null;
        $hnBranch = null;
        foreach ($branches as $b) {
            if (stripos($b['name'], 'HCM') !== false || stripos($b['name'], 'Hồ Chí Minh') !== false) {
                $hcmBranch = $b;
            }
            if (stripos($b['name'], 'HN') !== false || stripos($b['name'], 'Hà Nội') !== false) {
                $hnBranch = $b;
            }
        }
        if (!$hcmBranch) $hcmBranch = $branches[0];
        if (!$hnBranch) $hnBranch = $branches[1] ?? $branches[0];

        $transfers = [
            [
                'code' => 'TRF250001',
                'status' => 'in_transit',
                'from_branch_id' => $hcmBranch['id'],
                'to_branch_id' => $hnBranch['id'],
                'transfer_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'notes' => 'Chuyển hàng tháng 12',
                'total_items' => 3,
                'quantity_sent' => 5,
                'value_sent' => 1500000,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'code' => 'TRF250002',
                'status' => 'received',
                'from_branch_id' => $hnBranch['id'],
                'to_branch_id' => $hcmBranch['id'],
                'transfer_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'receive_date' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'notes' => 'Bổ sung hàng HCM',
                'total_items' => 2,
                'quantity_sent' => 3,
                'value_sent' => 900000,
                'quantity_received' => 3,
                'value_received' => 900000,
                'created_by' => $userId,
                'received_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
            [
                'code' => 'TRF250003',
                'status' => 'draft',
                'from_branch_id' => $hcmBranch['id'],
                'to_branch_id' => $hnBranch['id'],
                'notes' => 'Phiếu nháp',
                'total_items' => 1,
                'quantity_sent' => 2,
                'value_sent' => 600000,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code' => 'TRF250004',
                'status' => 'received',
                'from_branch_id' => $hcmBranch['id'],
                'to_branch_id' => $hnBranch['id'],
                'transfer_date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'receive_date' => date('Y-m-d H:i:s', strtotime('-9 days')),
                'notes' => '',
                'total_items' => 4,
                'quantity_sent' => 8,
                'value_sent' => 2400000,
                'quantity_received' => 8,
                'value_received' => 2400000,
                'created_by' => $userId,
                'received_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            ],
            [
                'code' => 'TRF250005',
                'status' => 'cancelled',
                'from_branch_id' => $hnBranch['id'],
                'to_branch_id' => $hcmBranch['id'],
                'notes' => 'Hủy do thay đổi kế hoạch',
                'total_items' => 2,
                'quantity_sent' => 4,
                'value_sent' => 1200000,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            ],
        ];

        foreach ($transfers as $transfer) {
            $this->db->table('stock_transfers')->insert($transfer);
            $transferId = $this->db->insertID();

            // Add items
            $numItems = $transfer['total_items'];
            for ($i = 0; $i < $numItems && $i < count($products); $i++) {
                $product = $products[$i];
                $qty = rand(1, 3);
                $price = rand(200000, 500000);
                
                $this->db->table('stock_transfer_items')->insert([
                    'transfer_id' => $transferId,
                    'product_id' => $product['id'],
                    'product_code' => $product['code'] ?? $product['sku'] ?? "SP{$product['id']}",
                    'product_name' => $product['name'],
                    'unit' => 'Cái',
                    'quantity_sent' => $qty,
                    'quantity_received' => $transfer['status'] === 'received' ? $qty : 0,
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                    'created_at' => $transfer['created_at'],
                ]);
            }
        }

        echo "Seeded " . count($transfers) . " stock transfers.\n";
    }
}
