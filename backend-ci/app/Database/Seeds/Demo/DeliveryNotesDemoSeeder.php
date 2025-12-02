<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * DeliveryNotesDemoSeeder - Demo delivery notes for testing delivery workflows
 * 
 * @agent-seeder: Demo delivery notes
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: OrdersDemoSeeder
 */
class DeliveryNotesDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo delivery notes...\n";
        
        $now = Time::now();

        $deliveryNotes = [
            [
                'id' => 1,
                'delivery_number' => 'DN-2024-001',
                'order_id' => 1,
                'customer_id' => 1,
                'branch_id' => 1,
                // 'warehouse_id' => 1, // Not in schema
                'delivery_date' => $now->subDays(10)->toDateTimeString(),
                'shipping_address' => '123 Nguyễn Trãi, Hà Nội', // delivery_address -> shipping_address
                // 'delivery_phone' => '0901234567', // Not in schema
                // 'delivery_contact' => 'Nguyễn Văn A', // Not in schema
                'carrier' => 'Giao Hàng Nhanh', // shipper_name -> carrier
                'tracking_number' => 'GHN123456789',
                'status' => 'delivered',
                'notes' => 'Giao hàng thành công',
            ],
            [
                'id' => 2,
                'delivery_number' => 'DN-2024-002',
                'order_id' => 2,
                'customer_id' => 2,
                'branch_id' => 1,
                'delivery_date' => $now->subDays(8)->toDateTimeString(),
                'shipping_address' => '456 Lê Lợi, Hà Nội',
                'carrier' => 'Viettel Post',
                'tracking_number' => 'VTP987654321',
                'status' => 'delivered',
                'notes' => 'Khách hàng hài lòng',
            ],
            [
                'id' => 3,
                'delivery_number' => 'DN-2024-003',
                'order_id' => 3,
                'customer_id' => 3,
                'branch_id' => 2,
                'delivery_date' => $now->subDays(5)->toDateTimeString(),
                'shipping_address' => '789 Nguyễn Huệ, HCM',
                'carrier' => 'J&T Express',
                'tracking_number' => 'JT456789123',
                'status' => 'in_transit', // Note: schema status varchar(30)
                'notes' => 'Đang trên đường giao',
            ],
            [
                'id' => 4,
                'delivery_number' => 'DN-2024-004',
                'order_id' => 4,
                'customer_id' => 4,
                'branch_id' => 1,
                'delivery_date' => $now->subDays(3)->toDateTimeString(),
                'shipping_address' => '321 Trần Hưng Đạo, Hà Nội',
                'carrier' => 'Ninja Van',
                'tracking_number' => 'NV789123456',
                'status' => 'pending', // Note: schema status default 'draft'
                'notes' => 'Chờ lấy hàng',
            ],
            [
                'id' => 5,
                'delivery_number' => 'DN-2024-005',
                'order_id' => 5,
                'customer_id' => 5,
                'branch_id' => 2,
                'delivery_date' => $now->subDays(2)->toDateTimeString(),
                'shipping_address' => '654 Lê Duẩn, HCM',
                'carrier' => 'Giao Hàng Tiết Kiệm',
                'tracking_number' => 'GHTK321654987',
                'status' => 'failed',
                'notes' => 'Không liên lạc được khách hàng',
            ],
            [
                'id' => 6,
                'delivery_number' => 'DN-2024-006',
                'order_id' => 6,
                'customer_id' => 6,
                'branch_id' => 1,
                'delivery_date' => $now->subDays(1)->toDateTimeString(),
                'shipping_address' => '147 Phố Huế, Hà Nội',
                'carrier' => 'Best Express',
                'tracking_number' => 'BE654987321',
                'status' => 'delivered',
                'notes' => 'Giao hàng đúng giờ',
            ],
            [
                'id' => 7,
                'delivery_number' => 'DN-2024-007',
                'order_id' => 7,
                'customer_id' => 7,
                'branch_id' => 2,
                'delivery_date' => $now->toDateTimeString(),
                'shipping_address' => '258 Hai Bà Trưng, HCM',
                'carrier' => 'Kerry Express',
                'tracking_number' => 'KE987321654',
                'status' => 'preparing',
                'notes' => 'Đang chuẩn bị hàng',
            ],
        ];

        foreach ($deliveryNotes as $note) {
            $note['created_at'] = $now;
            $note['updated_at'] = $now;
            $this->db->table('delivery_notes')->ignore(true)->insert($note);
        }

        echo "      ✓ Created " . count($deliveryNotes) . " demo delivery notes\n";
    }
}