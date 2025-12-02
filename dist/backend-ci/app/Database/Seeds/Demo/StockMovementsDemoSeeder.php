<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * StockMovementsDemoSeeder - Demo stock movements for testing inventory workflows
 * 
 * @agent-seeder: Demo stock movements
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: WarehousesDemoSeeder, ProductsDemoSeeder
 */
class StockMovementsDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo stock movements...\n";
        
        $now = Time::now();

        $movements = [
            // Stock IN movements
            [
                'id' => 1,
                'code' => 'IN-2024-001',
                'type' => 'in',
                'warehouse_id' => 1,
                'reference_type' => 'purchase_order',
                'reference_id' => 1,
                'movement_date' => $now->subDays(25)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Nhập hàng từ đơn mua PO-2024-001',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'code' => 'IN-2024-002',
                'type' => 'in',
                'warehouse_id' => 1,
                'reference_type' => 'purchase_order',
                'reference_id' => 2,
                'movement_date' => $now->subDays(20)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Nhập hàng từ đơn mua PO-2024-002',
                'created_by' => 2,
            ],
            [
                'id' => 3,
                'code' => 'IN-2024-003',
                'type' => 'in',
                'warehouse_id' => 3,
                'reference_type' => 'purchase_order',
                'reference_id' => 3,
                'movement_date' => $now->subDays(15)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Nhập hàng từ đơn mua PO-2024-003',
                'created_by' => 1,
            ],
            
            // Stock OUT movements
            [
                'id' => 4,
                'code' => 'OUT-2024-001',
                'type' => 'out',
                'warehouse_id' => 1,
                'reference_type' => 'order',
                'reference_id' => 1,
                'movement_date' => $now->subDays(12)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Xuất hàng cho đơn ORD-2024-001',
                'created_by' => 1,
            ],
            [
                'id' => 5,
                'code' => 'OUT-2024-002',
                'type' => 'out',
                'warehouse_id' => 1,
                'reference_type' => 'order',
                'reference_id' => 2,
                'movement_date' => $now->subDays(10)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Xuất hàng cho đơn ORD-2024-002',
                'created_by' => 2,
            ],
            [
                'id' => 6,
                'code' => 'OUT-2024-003',
                'type' => 'out',
                'warehouse_id' => 3,
                'reference_type' => 'order',
                'reference_id' => 3,
                'movement_date' => $now->subDays(8)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Xuất hàng cho đơn ORD-2024-003',
                'created_by' => 1,
            ],
            
            // Transfer movements
            [
                'id' => 7,
                'code' => 'TRF-2024-001',
                'type' => 'transfer',
                'warehouse_id' => 1,
                'to_warehouse_id' => 2,
                'reference_type' => 'transfer',
                'reference_id' => null,
                'movement_date' => $now->subDays(7)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Chuyển hàng từ kho chính sang kho bán lẻ',
                'created_by' => 1,
            ],
            [
                'id' => 8,
                'code' => 'TRF-2024-002',
                'type' => 'transfer',
                'warehouse_id' => 3,
                'to_warehouse_id' => 1,
                'reference_type' => 'transfer',
                'reference_id' => null,
                'movement_date' => $now->subDays(5)->toDateTimeString(),
                'status' => 'in_transit',
                'notes' => 'Chuyển hàng từ HCM về Hà Nội',
                'created_by' => 2,
            ],
            
            // Adjustment movements
            [
                'id' => 9,
                'code' => 'ADJ-2024-001',
                'type' => 'adjustment',
                'warehouse_id' => 1,
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'movement_date' => $now->subDays(3)->toDateTimeString(),
                'status' => 'completed',
                'notes' => 'Điều chỉnh tồn kho sau kiểm kê',
                'created_by' => 1,
            ],
            [
                'id' => 10,
                'code' => 'ADJ-2024-002',
                'type' => 'adjustment',
                'warehouse_id' => 3,
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'movement_date' => $now->subDays(1)->toDateTimeString(),
                'status' => 'pending',
                'notes' => 'Điều chỉnh hàng hỏng',
                'created_by' => 2,
            ],
        ];

        foreach ($movements as $movement) {
            $movement['created_at'] = $now;
            $movement['updated_at'] = $now;
            $this->db->table('stock_movements')->ignore(true)->insert($movement);
        }

        // Stock movement items
        $movementItems = [
            // IN-2024-001 items
            ['movement_id' => 1, 'product_id' => 1, 'quantity' => 100, 'unit_price' => 400000],
            ['movement_id' => 1, 'product_id' => 2, 'quantity' => 20, 'unit_price' => 500000],
            
            // IN-2024-002 items
            ['movement_id' => 2, 'product_id' => 3, 'quantity' => 50, 'unit_price' => 450000],
            ['movement_id' => 2, 'product_id' => 4, 'quantity' => 25, 'unit_price' => 500000],
            
            // IN-2024-003 items
            ['movement_id' => 3, 'product_id' => 5, 'quantity' => 30, 'unit_price' => 1500000],
            
            // OUT-2024-001 items
            ['movement_id' => 4, 'product_id' => 1, 'quantity' => -1, 'unit_price' => 500000],
            
            // OUT-2024-002 items
            ['movement_id' => 5, 'product_id' => 2, 'quantity' => -1, 'unit_price' => 800000],
            
            // OUT-2024-003 items
            ['movement_id' => 6, 'product_id' => 3, 'quantity' => -2, 'unit_price' => 600000],
            
            // TRF-2024-001 items
            ['movement_id' => 7, 'product_id' => 1, 'quantity' => -10, 'unit_price' => 400000],
            ['movement_id' => 7, 'product_id' => 2, 'quantity' => -5, 'unit_price' => 500000],
            
            // TRF-2024-002 items
            ['movement_id' => 8, 'product_id' => 5, 'quantity' => -3, 'unit_price' => 1500000],
            
            // ADJ-2024-001 items
            ['movement_id' => 9, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 400000],
            ['movement_id' => 9, 'product_id' => 3, 'quantity' => -1, 'unit_price' => 450000],
            
            // ADJ-2024-002 items
            ['movement_id' => 10, 'product_id' => 4, 'quantity' => -2, 'unit_price' => 500000],
        ];

        foreach ($movementItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('stock_movement_items')->ignore(true)->insert($item);
        }

        echo "      ✓ Created " . count($movements) . " demo stock movements\n";
        echo "      ✓ Created " . count($movementItems) . " movement items\n";
    }
}