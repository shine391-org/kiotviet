<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * PurchaseOrdersDemoSeeder - Demo purchase orders for testing procurement workflows
 * 
 * @agent-seeder: Demo purchase orders
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: SuppliersDemoSeeder, ProductsDemoSeeder
 */
class PurchaseOrdersDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo purchase orders...\n";

        // Skip if schema không có các cột cần thiết (supplier_id/payment_status/total_amount/warehouse_id)
        $requiredTables = ['purchase_orders', 'purchase_order_items'];
        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                echo "      ⚠ Skipped Purchase Orders demo (missing table: {$table})\n";
                return;
            }
        }
        $requiredColumns = ['partner_id', 'order_date', 'expected_date', 'total_amount', 'paid_amount', 'payment_status', 'created_by'];
        foreach ($requiredColumns as $col) {
            if (! $this->db->fieldExists($col, 'purchase_orders')) {
                echo "      ⚠ Skipped Purchase Orders demo (missing column: {$col})\n";
                return;
            }
        }
        
        // Cleanup existing demo purchase orders
        $this->cleanupExisting();
        
        $now = Time::now();

        $purchaseOrders = [
            [
                'order_number' => 'PO-2024-001',
                'partner_id' => 1,
                'supplier_id' => 1,
                'branch_id' => 1,
                'user_id' => 1,
                'order_date' => $now->subDays(30)->toDateTimeString(),
                'expected_date' => $now->subDays(23)->toDateTimeString(),
                'status' => 'completed',
                'total' => 50000000,
                'paid_amount' => 50000000,
                'payment_status' => 'paid',
                'notes' => 'Đơn hàng đầu tiên trong tháng',
                'created_by' => 1,
            ],
            [
                'order_number' => 'PO-2024-002',
                'partner_id' => 2,
                'supplier_id' => 2,
                'branch_id' => 1,
                'user_id' => 2,
                'order_date' => $now->subDays(25)->toDateTimeString(),
                'expected_date' => $now->subDays(18)->toDateTimeString(),
                'status' => 'completed',
                'total' => 35000000,
                'paid_amount' => 35000000,
                'payment_status' => 'paid',
                'notes' => 'Nhập hàng túi xách',
                'created_by' => 2,
            ],
            [
                'order_number' => 'PO-2024-003',
                'partner_id' => 3,
                'supplier_id' => 3,
                'branch_id' => 2,
                'user_id' => 1,
                'order_date' => $now->subDays(20)->toDateTimeString(),
                'expected_date' => $now->subDays(13)->toDateTimeString(),
                'status' => 'received',
                'total' => 45000000,
                'paid_amount' => 22500000,
                'payment_status' => 'partial',
                'notes' => 'Đã nhận hàng, chờ thanh toán phần còn lại',
                'created_by' => 1,
            ],
            [
                'order_number' => 'PO-2024-004',
                'partner_id' => 4,
                'supplier_id' => 4,
                'branch_id' => 1,
                'user_id' => 2,
                'order_date' => $now->subDays(15)->toDateTimeString(),
                'expected_date' => $now->subDays(8)->toDateTimeString(),
                'status' => 'in_transit',
                'total' => 60000000,
                'paid_amount' => 30000000,
                'payment_status' => 'partial',
                'notes' => 'Hàng đang trên đường về kho',
                'created_by' => 2,
            ],
            [
                'order_number' => 'PO-2024-005',
                'partner_id' => 5,
                'supplier_id' => 5,
                'branch_id' => 2,
                'user_id' => 1,
                'order_date' => $now->subDays(10)->toDateTimeString(),
                'expected_date' => $now->subDays(3)->toDateTimeString(),
                'status' => 'confirmed',
                'total' => 25000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Nhà cung cấp đã xác nhận đơn',
                'created_by' => 1,
            ],
            [
                'order_number' => 'PO-2024-006',
                'partner_id' => 6,
                'supplier_id' => 6,
                'branch_id' => 1,
                'user_id' => 2,
                'order_date' => $now->subDays(7)->toDateTimeString(),
                'expected_date' => $now->addDays(7)->toDateTimeString(),
                'status' => 'pending',
                'total' => 40000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Chờ nhà cung cấp xác nhận',
                'created_by' => 2,
            ],
            [
                'order_number' => 'PO-2024-007',
                'partner_id' => 7,
                'supplier_id' => 7,
                'branch_id' => 2,
                'user_id' => 1,
                'order_date' => $now->subDays(5)->toDateTimeString(),
                'expected_date' => $now->addDays(10)->toDateTimeString(),
                'status' => 'draft',
                'total' => 55000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Đơn nháp, chưa gửi cho nhà cung cấp',
                'created_by' => 1,
            ],
            [
                'order_number' => 'PO-2024-008',
                'partner_id' => 8,
                'supplier_id' => 8,
                'branch_id' => 1,
                'user_id' => 2,
                'order_date' => $now->subDays(3)->toDateTimeString(),
                'expected_date' => $now->addDays(12)->toDateTimeString(),
                'status' => 'cancelled',
                'total' => 30000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Hủy do nhà cung cấp không đủ hàng',
                'created_by' => 2,
            ],
        ];

        foreach ($purchaseOrders as $po) {
            $po['created_at'] = $now;
            $po['updated_at'] = $now;
            $result = $this->db->table('purchase_orders')->insert($po);
        }
        
        // Fetch the actual IDs of the purchase orders we just created
        $poIds = $this->db->table('purchase_orders')
            ->select('id, order_number')
            ->like('order_number', 'PO-2024-', 'after')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        
        if (count($poIds) < 8) {
            echo "      ⚠ Warning: Expected 8 purchase orders, found " . count($poIds) . "\n";
            return;
        }

        // Purchase order items (sample items for each PO)
        $poItems = [
            // PO-1 items
            ['purchase_order_id' => $poIds[0]['id'], 'product_id' => 501, 'quantity' => 100, 'unit_price' => 400000, 'total_price' => 40000000],
            ['purchase_order_id' => $poIds[0]['id'], 'product_id' => 502, 'quantity' => 20, 'unit_price' => 500000, 'total_price' => 10000000],
            
            // PO-2 items
            ['purchase_order_id' => $poIds[1]['id'], 'product_id' => 503, 'quantity' => 50, 'unit_price' => 450000, 'total_price' => 22500000],
            ['purchase_order_id' => $poIds[1]['id'], 'product_id' => 501, 'quantity' => 25, 'unit_price' => 500000, 'total_price' => 12500000],
            
            // PO-3 items
            ['purchase_order_id' => $poIds[2]['id'], 'product_id' => 502, 'quantity' => 30, 'unit_price' => 1500000, 'total_price' => 45000000],
            
            // PO-4 items
            ['purchase_order_id' => $poIds[3]['id'], 'product_id' => 501, 'quantity' => 150, 'unit_price' => 400000, 'total_price' => 60000000],
            
            // PO-5 items
            ['purchase_order_id' => $poIds[4]['id'], 'product_id' => 502, 'quantity' => 50, 'unit_price' => 500000, 'total_price' => 25000000],
            
            // PO-6 items
            ['purchase_order_id' => $poIds[5]['id'], 'product_id' => 503, 'quantity' => 80, 'unit_price' => 500000, 'total_price' => 40000000],
            
            // PO-7 items
            ['purchase_order_id' => $poIds[6]['id'], 'product_id' => 501, 'quantity' => 110, 'unit_price' => 500000, 'total_price' => 55000000],
            
            // PO-8 items (cancelled)
            ['purchase_order_id' => $poIds[7]['id'], 'product_id' => 502, 'quantity' => 20, 'unit_price' => 1500000, 'total_price' => 30000000],
        ];

        foreach ($poItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('purchase_order_items')->ignore(true)->insert($item);
        }

        echo "      ✓ Created " . count($purchaseOrders) . " demo purchase orders\n";
        echo "      ✓ Created " . count($poItems) . " purchase order items\n";
        
        // Seed Goods Receipts (phiếu nhập kho) for completed/received POs
        $this->seedGoodsReceipts($poIds, $now);
        
        // Seed Supplier Debt Transactions (công nợ nhà cung cấp)
        $this->seedSupplierDebt($purchaseOrders, $now);
    }
    
    private function seedGoodsReceipts(array $poIds, $now): void
    {
        if (!$this->db->tableExists('goods_receipts') || !$this->db->tableExists('goods_receipt_items')) {
            return;
        }
        
        // Cleanup old demo data
        $this->db->table('goods_receipts')->like('receipt_number', 'GRN-2024-', 'after')->delete();
        
        // Create goods receipts for completed/received POs (first 3)
        $receipts = [
            [
                'receipt_number' => 'GRN-2024-001',
                'purchase_order_id' => $poIds[0]['id'],
                'branch_id' => 1,
                'status' => 'completed',
                'created_at' => $now->subDays(23),
                'updated_at' => $now->subDays(23),
            ],
            [
                'receipt_number' => 'GRN-2024-002',
                'purchase_order_id' => $poIds[1]['id'],
                'branch_id' => 1,
                'status' => 'completed',
                'created_at' => $now->subDays(18),
                'updated_at' => $now->subDays(18),
            ],
            [
                'receipt_number' => 'GRN-2024-003',
                'purchase_order_id' => $poIds[2]['id'],
                'branch_id' => 2,
                'status' => 'completed',
                'created_at' => $now->subDays(13),
                'updated_at' => $now->subDays(13),
            ],
        ];
        
        foreach ($receipts as $receipt) {
            $this->db->table('goods_receipts')->insert($receipt);
        }
        
        // Get inserted receipt IDs
        $grnIds = $this->db->table('goods_receipts')
            ->select('id, purchase_order_id')
            ->like('receipt_number', 'GRN-2024-', 'after')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        
        if (empty($grnIds)) {
            return;
        }
        
        // Goods receipt items
        $grnItems = [
            // GRN-1 (from PO-1)
            ['goods_receipt_id' => $grnIds[0]['id'], 'product_id' => 501, 'quantity' => 100, 'rate' => 400000, 'amount' => 40000000],
            ['goods_receipt_id' => $grnIds[0]['id'], 'product_id' => 502, 'quantity' => 20, 'rate' => 500000, 'amount' => 10000000],
            // GRN-2 (from PO-2)
            ['goods_receipt_id' => $grnIds[1]['id'], 'product_id' => 503, 'quantity' => 50, 'rate' => 450000, 'amount' => 22500000],
            ['goods_receipt_id' => $grnIds[1]['id'], 'product_id' => 501, 'quantity' => 25, 'rate' => 500000, 'amount' => 12500000],
            // GRN-3 (from PO-3)
            ['goods_receipt_id' => $grnIds[2]['id'], 'product_id' => 502, 'quantity' => 30, 'rate' => 1500000, 'amount' => 45000000],
        ];
        
        foreach ($grnItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('goods_receipt_items')->insert($item);
        }
        
        echo "      ✓ Created " . count($receipts) . " goods receipts\n";
        echo "      ✓ Created " . count($grnItems) . " goods receipt items\n";
    }
    
    private function seedSupplierDebt(array $purchaseOrders, $now): void
    {
        if (!$this->db->tableExists('supplier_debt_transactions')) {
            return;
        }
        
        // Cleanup old demo data
        $this->db->table('supplier_debt_transactions')->where('reference_type', 'purchase_order')->delete();
        
        $debtTransactions = [];
        $partnerDebts = []; // Track debt per partner
        
        foreach ($purchaseOrders as $po) {
            $partnerId = $po['partner_id'];
            $total = $po['total'];
            $paid = $po['paid_amount'];
            $unpaid = $total - $paid;
            
            // Skip cancelled or draft orders
            if (in_array($po['status'], ['cancelled', 'draft'])) {
                continue;
            }
            
            // Initialize partner debt tracking
            if (!isset($partnerDebts[$partnerId])) {
                $partnerDebts[$partnerId] = 0;
            }
            
            $debtBefore = $partnerDebts[$partnerId];
            
            // Transaction 1: Debt increase from purchase (nhập hàng tăng nợ)
            if ($unpaid > 0) {
                $debtTransactions[] = [
                    'partner_id' => $partnerId,
                    'type' => 'adjust',
                    'amount' => $total,
                    'debt_before' => $debtBefore,
                    'debt_after' => $debtBefore + $total,
                    'payment_method' => null,
                    'executor_id' => $po['created_by'],
                    'note' => "Nhập hàng từ đơn {$po['order_number']}",
                    'reference_type' => 'purchase_order',
                    'reference_id' => null, // Will be updated later if needed
                    'transaction_date' => $po['order_date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $partnerDebts[$partnerId] += $total;
            }
            
            // Transaction 2: Payment (thanh toán giảm nợ)
            if ($paid > 0) {
                $debtBefore = $partnerDebts[$partnerId];
                $debtTransactions[] = [
                    'partner_id' => $partnerId,
                    'type' => 'payment',
                    'amount' => $paid,
                    'debt_before' => $debtBefore,
                    'debt_after' => $debtBefore - $paid,
                    'payment_method' => 'bank_transfer',
                    'executor_id' => $po['created_by'],
                    'note' => "Thanh toán đơn {$po['order_number']}",
                    'reference_type' => 'purchase_order',
                    'reference_id' => null,
                    'transaction_date' => $po['order_date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $partnerDebts[$partnerId] -= $paid;
            }
        }
        
        foreach ($debtTransactions as $tx) {
            $this->db->table('supplier_debt_transactions')->insert($tx);
        }
        
        // Update partner debt_amount
        foreach ($partnerDebts as $partnerId => $debt) {
            if ($debt > 0) {
                $this->db->table('partners')
                    ->where('id', $partnerId)
                    ->update(['debt_amount' => $debt]);
            }
        }
        
        echo "      ✓ Created " . count($debtTransactions) . " supplier debt transactions\n";
        echo "      ✓ Updated " . count($partnerDebts) . " partner debt amounts\n";
    }
    
    private function cleanupExisting(): void
    {
        // Delete existing demo purchase orders by order_number pattern
        $this->db->table('purchase_orders')->like('order_number', 'PO-2024-', 'after')->delete();
    }
}
