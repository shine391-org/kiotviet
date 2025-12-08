<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StockReconciliationSeeder extends Seeder
{
    public function run()
    {
        // Get some products and a user for sample data
        $products = $this->db->table('products')->limit(5)->get()->getResultArray();
        $user = $this->db->table('users')->where('status', 'active')->get()->getRowArray();
        $branch = $this->db->table('branches')->get()->getRowArray();
        
        $userId = $user['id'] ?? 1;
        $branchId = $branch['id'] ?? 1;
        $now = date('Y-m-d H:i:s');
        
        // Create sample stock reconciliations
        $reconciliations = [
            [
                'recon_number' => 'SK1-' . date('Ymd') . '001',
                'branch_id' => $branchId,
                'status' => 'approved',
                'notes' => 'Kiểm kho cuối tuần - đã cân bằng',
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            ],
            [
                'recon_number' => 'SK1-' . date('Ymd') . '002',
                'branch_id' => $branchId,
                'status' => 'draft',
                'notes' => 'Kiểm kho tháng 12 - đang thực hiện',
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'approved_by' => null,
                'approved_at' => null,
            ],
            [
                'recon_number' => 'SK1-' . date('Ymd') . '003',
                'branch_id' => $branchId,
                'status' => 'draft',
                'notes' => 'Kiểm kho hàng điện tử',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'approved_by' => null,
                'approved_at' => null,
            ],
            [
                'recon_number' => 'SK1-' . date('Ymd') . '004',
                'branch_id' => $branchId,
                'status' => 'rejected',
                'notes' => 'Đã hủy do sai thông tin nhập',
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-9 days')),
                'approved_by' => null,
                'approved_at' => null,
            ],
        ];

        foreach ($reconciliations as $recon) {
            $this->db->table('stock_reconciliations')->insert($recon);
            $reconId = $this->db->insertID();
            
            // Add items for each reconciliation if we have products
            if (!empty($products)) {
                $items = [];
                foreach (array_slice($products, 0, 3) as $i => $product) {
                    $currentQty = rand(10, 100);
                    $countedQty = $currentQty + rand(-5, 10); // Some variance
                    $items[] = [
                        'reconciliation_id' => $reconId,
                        'product_id' => $product['id'],
                        'current_qty' => $currentQty,
                        'counted_qty' => $countedQty,
                        'variance_qty' => $countedQty - $currentQty,
                        'unit_cost' => $product['cost_price'] ?? rand(10000, 500000),
                        'created_at' => $recon['created_at'],
                        'updated_at' => $recon['updated_at'],
                    ];
                }
                
                if (!empty($items)) {
                    $this->db->table('stock_reconciliation_items')->insertBatch($items);
                }
            }
        }

        echo "Seeded " . count($reconciliations) . " stock reconciliations with items.\n";
    }
}
