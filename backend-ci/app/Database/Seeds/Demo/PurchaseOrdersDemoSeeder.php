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
        $requiredColumns = ['supplier_id', 'warehouse_id', 'order_date', 'expected_date', 'total_amount', 'paid_amount', 'payment_status', 'created_by'];
        foreach ($requiredColumns as $col) {
            if (! $this->db->fieldExists($col, 'purchase_orders')) {
                echo "      ⚠ Skipped Purchase Orders demo (missing column: {$col})\n";
                return;
            }
        }
        
        $now = Time::now();

        $purchaseOrders = [
            [
                'id' => 1,
                'code' => 'PO-2024-001',
                'supplier_id' => 1,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'order_date' => $now->subDays(30)->toDateTimeString(),
                'expected_date' => $now->subDays(23)->toDateTimeString(),
                'status' => 'completed',
                'total_amount' => 50000000,
                'paid_amount' => 50000000,
                'payment_status' => 'paid',
                'notes' => 'Đơn hàng đầu tiên trong tháng',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'code' => 'PO-2024-002',
                'supplier_id' => 2,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'order_date' => $now->subDays(25)->toDateTimeString(),
                'expected_date' => $now->subDays(18)->toDateTimeString(),
                'status' => 'completed',
                'total_amount' => 35000000,
                'paid_amount' => 35000000,
                'payment_status' => 'paid',
                'notes' => 'Nhập hàng túi xách',
                'created_by' => 2,
            ],
            [
                'id' => 3,
                'code' => 'PO-2024-003',
                'supplier_id' => 3,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'order_date' => $now->subDays(20)->toDateTimeString(),
                'expected_date' => $now->subDays(13)->toDateTimeString(),
                'status' => 'received',
                'total_amount' => 45000000,
                'paid_amount' => 22500000,
                'payment_status' => 'partial',
                'notes' => 'Đã nhận hàng, chờ thanh toán phần còn lại',
                'created_by' => 1,
            ],
            [
                'id' => 4,
                'code' => 'PO-2024-004',
                'supplier_id' => 4,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'order_date' => $now->subDays(15)->toDateTimeString(),
                'expected_date' => $now->subDays(8)->toDateTimeString(),
                'status' => 'in_transit',
                'total_amount' => 60000000,
                'paid_amount' => 30000000,
                'payment_status' => 'partial',
                'notes' => 'Hàng đang trên đường về kho',
                'created_by' => 2,
            ],
            [
                'id' => 5,
                'code' => 'PO-2024-005',
                'supplier_id' => 5,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'order_date' => $now->subDays(10)->toDateTimeString(),
                'expected_date' => $now->subDays(3)->toDateTimeString(),
                'status' => 'confirmed',
                'total_amount' => 25000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Nhà cung cấp đã xác nhận đơn',
                'created_by' => 1,
            ],
            [
                'id' => 6,
                'code' => 'PO-2024-006',
                'supplier_id' => 6,
                'branch_id' => 1,
                'warehouse_id' => 2,
                'order_date' => $now->subDays(7)->toDateTimeString(),
                'expected_date' => $now->addDays(7)->toDateTimeString(),
                'status' => 'pending',
                'total_amount' => 40000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Chờ nhà cung cấp xác nhận',
                'created_by' => 2,
            ],
            [
                'id' => 7,
                'code' => 'PO-2024-007',
                'supplier_id' => 7,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'order_date' => $now->subDays(5)->toDateTimeString(),
                'expected_date' => $now->addDays(10)->toDateTimeString(),
                'status' => 'draft',
                'total_amount' => 55000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Đơn nháp, chưa gửi cho nhà cung cấp',
                'created_by' => 1,
            ],
            [
                'id' => 8,
                'code' => 'PO-2024-008',
                'supplier_id' => 8,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'order_date' => $now->subDays(3)->toDateTimeString(),
                'expected_date' => $now->addDays(12)->toDateTimeString(),
                'status' => 'cancelled',
                'total_amount' => 30000000,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => 'Hủy do nhà cung cấp không đủ hàng',
                'created_by' => 2,
            ],
        ];

        foreach ($purchaseOrders as $po) {
            $po['created_at'] = $now;
            $po['updated_at'] = $now;
            $this->db->table('purchase_orders')->ignore(true)->insert($po);
        }

        // Purchase order items (sample items for each PO)
        $poItems = [
            // PO-1 items
            ['purchase_order_id' => 1, 'product_id' => 1, 'quantity' => 100, 'unit_price' => 400000, 'total_price' => 40000000],
            ['purchase_order_id' => 1, 'product_id' => 2, 'quantity' => 20, 'unit_price' => 500000, 'total_price' => 10000000],
            
            // PO-2 items
            ['purchase_order_id' => 2, 'product_id' => 3, 'quantity' => 50, 'unit_price' => 450000, 'total_price' => 22500000],
            ['purchase_order_id' => 2, 'product_id' => 4, 'quantity' => 25, 'unit_price' => 500000, 'total_price' => 12500000],
            
            // PO-3 items
            ['purchase_order_id' => 3, 'product_id' => 5, 'quantity' => 30, 'unit_price' => 1500000, 'total_price' => 45000000],
            
            // PO-4 items
            ['purchase_order_id' => 4, 'product_id' => 1, 'quantity' => 150, 'unit_price' => 400000, 'total_price' => 60000000],
            
            // PO-5 items
            ['purchase_order_id' => 5, 'product_id' => 2, 'quantity' => 50, 'unit_price' => 500000, 'total_price' => 25000000],
            
            // PO-6 items
            ['purchase_order_id' => 6, 'product_id' => 3, 'quantity' => 80, 'unit_price' => 500000, 'total_price' => 40000000],
            
            // PO-7 items
            ['purchase_order_id' => 7, 'product_id' => 4, 'quantity' => 110, 'unit_price' => 500000, 'total_price' => 55000000],
            
            // PO-8 items (cancelled)
            ['purchase_order_id' => 8, 'product_id' => 5, 'quantity' => 20, 'unit_price' => 1500000, 'total_price' => 30000000],
        ];

        foreach ($poItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('purchase_order_items')->ignore(true)->insert($item);
        }

        echo "      ✓ Created " . count($purchaseOrders) . " demo purchase orders\n";
        echo "      ✓ Created " . count($poItems) . " purchase order items\n";
    }
}
