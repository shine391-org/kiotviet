<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * ReturnsDemoSeeder - Demo return orders for testing return workflows
 * 
 * @agent-seeder: Demo returns
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: OrdersDemoSeeder, ProductsDemoSeeder
 */
class ReturnsDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo return orders...\n";
        
        $now = Time::now();

        // Return orders
        $returns = [
            [
                'id' => 1,
                'code' => 'RET-2024-001',
                'order_id' => 1, // From OrdersDemoSeeder
                'customer_id' => 1,
                'branch_id' => 1,
                'return_date' => $now->subDays(5)->toDateTimeString(),
                'reason' => 'Sản phẩm bị lỗi',
                'status' => 'completed',
                'total_amount' => 500000,
                'refund_amount' => 500000,
                'refund_method' => 'cash',
                'notes' => 'Khách hàng phát hiện lỗi sau 2 ngày sử dụng',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'code' => 'RET-2024-002',
                'order_id' => 2,
                'customer_id' => 2,
                'branch_id' => 1,
                'return_date' => $now->subDays(3)->toDateTimeString(),
                'reason' => 'Không đúng size',
                'status' => 'completed',
                'total_amount' => 800000,
                'refund_amount' => 800000,
                'refund_method' => 'bank_transfer',
                'notes' => 'Đổi size khác',
                'created_by' => 2,
            ],
            [
                'id' => 3,
                'code' => 'RET-2024-003',
                'order_id' => 3,
                'customer_id' => 3,
                'branch_id' => 2,
                'return_date' => $now->subDays(2)->toDateTimeString(),
                'reason' => 'Không đúng màu',
                'status' => 'pending',
                'total_amount' => 1200000,
                'refund_amount' => 1200000,
                'refund_method' => 'cash',
                'notes' => 'Chờ kiểm tra hàng',
                'created_by' => 1,
            ],
            [
                'id' => 4,
                'code' => 'RET-2024-004',
                'order_id' => 4,
                'customer_id' => 4,
                'branch_id' => 1,
                'return_date' => $now->subDays(1)->toDateTimeString(),
                'reason' => 'Sản phẩm bị hư hỏng khi vận chuyển',
                'status' => 'approved',
                'total_amount' => 1500000,
                'refund_amount' => 1500000,
                'refund_method' => 'bank_transfer',
                'notes' => 'Đã xác nhận lỗi vận chuyển',
                'created_by' => 2,
            ],
            [
                'id' => 5,
                'code' => 'RET-2024-005',
                'order_id' => 5,
                'customer_id' => 5,
                'branch_id' => 2,
                'return_date' => $now->toDateTimeString(),
                'reason' => 'Khách hàng đổi ý',
                'status' => 'rejected',
                'total_amount' => 2000000,
                'refund_amount' => 0,
                'refund_method' => null,
                'notes' => 'Quá thời gian đổi trả (7 ngày)',
                'created_by' => 1,
            ],
        ];

        foreach ($returns as $return) {
            $return['created_at'] = $now;
            $return['updated_at'] = $now;
            $this->db->table('returns')->ignore(true)->insert($return);
        }

        // Return items
        $returnItems = [
            // Return 1 items
            [
                'return_id' => 1,
                'product_id' => 1,
                'variant_id' => null,
                'quantity' => 1,
                'unit_price' => 500000,
                'total_price' => 500000,
                'reason' => 'Khóa kéo bị hỏng',
            ],
            // Return 2 items
            [
                'return_id' => 2,
                'product_id' => 2,
                'variant_id' => 1,
                'quantity' => 1,
                'unit_price' => 800000,
                'total_price' => 800000,
                'reason' => 'Size quá nhỏ',
            ],
            // Return 3 items
            [
                'return_id' => 3,
                'product_id' => 3,
                'variant_id' => null,
                'quantity' => 2,
                'unit_price' => 600000,
                'total_price' => 1200000,
                'reason' => 'Màu không đúng như hình',
            ],
            // Return 4 items
            [
                'return_id' => 4,
                'product_id' => 4,
                'variant_id' => 2,
                'quantity' => 1,
                'unit_price' => 1500000,
                'total_price' => 1500000,
                'reason' => 'Bị rách do vận chuyển',
            ],
            // Return 5 items (rejected)
            [
                'return_id' => 5,
                'product_id' => 5,
                'variant_id' => null,
                'quantity' => 1,
                'unit_price' => 2000000,
                'total_price' => 2000000,
                'reason' => 'Đổi ý không muốn mua',
            ],
        ];

        foreach ($returnItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $this->db->table('return_items')->ignore(true)->insert($item);
        }

        echo "      ✓ Created " . count($returns) . " demo returns\n";
        echo "      ✓ Created " . count($returnItems) . " return items\n";
    }
}