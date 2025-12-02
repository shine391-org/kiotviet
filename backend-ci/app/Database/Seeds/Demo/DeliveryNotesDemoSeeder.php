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
                'code' => 'DN-2024-001',
                'order_id' => 1,
                'customer_id' => 1,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'delivery_date' => $now->subDays(10)->toDateTimeString(),
                'delivery_address' => '123 Nguyễn Trãi, Hà Nội',
                'delivery_phone' => '0901234567',
                'delivery_contact' => 'Nguyễn Văn A',
                'shipper_name' => 'Giao Hàng Nhanh',
                'tracking_number' => 'GHN123456789',
                'status' => 'delivered',
                'notes' => 'Giao hàng thành công',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'code' => 'DN-2024-002',
                'order_id' => 2,
                'customer_id' => 2,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'delivery_date' => $now->subDays(8)->toDateTimeString(),
                'delivery_address' => '456 Lê Lợi, Hà Nội',
                'delivery_phone' => '0907654321',
                'delivery_contact' => 'Trần Thị B',
                'shipper_name' => 'Viettel Post',
                'tracking_number' => 'VTP987654321',
                'status' => 'delivered',
                'notes' => 'Khách hàng hài lòng',
                'created_by' => 2,
            ],
            [
                'id' => 3,
                'code' => 'DN-2024-003',
                'order_id' => 3,
                'customer_id' => 3,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'delivery_date' => $now->subDays(5)->toDateTimeString(),
                'delivery_address' => '789 Nguyễn Huệ, HCM',
                'delivery_phone' => '0912345678',
                'delivery_contact' => 'Lê Văn C',
                'shipper_name' => 'J&T Express',
                'tracking_number' => 'JT456789123',
                'status' => 'in_transit',
                'notes' => 'Đang trên đường giao',
                'created_by' => 1,
            ],
            [
                'id' => 4,
                'code' => 'DN-2024-004',
                'order_id' => 4,
                'customer_id' => 4,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'delivery_date' => $now->subDays(3)->toDateTimeString(),
                'delivery_address' => '321 Trần Hưng Đạo, Hà Nội',
                'delivery_phone' => '0923456789',
                'delivery_contact' => 'Phạm Thị D',
                'shipper_name' => 'Ninja Van',
                'tracking_number' => 'NV789123456',
                'status' => 'pending',
                'notes' => 'Chờ lấy hàng',
                'created_by' => 2,
            ],
            [
                'id' => 5,
                'code' => 'DN-2024-005',
                'order_id' => 5,
                'customer_id' => 5,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'delivery_date' => $now->subDays(2)->toDateTimeString(),
                'delivery_address' => '654 Lê Duẩn, HCM',
                'delivery_phone' => '0934567890',
                'delivery_contact' => 'Hoàng Văn E',
                'shipper_name' => 'Giao Hàng Tiết Kiệm',
                'tracking_number' => 'GHTK321654987',
                'status' => 'failed',
                'notes' => 'Không liên lạc được khách hàng',
                'created_by' => 1,
            ],
            [
                'id' => 6,
                'code' => 'DN-2024-006',
                'order_id' => 6,
                'customer_id' => 6,
                'branch_id' => 1,
                'warehouse_id' => 2,
                'delivery_date' => $now->subDays(1)->toDateTimeString(),
                'delivery_address' => '147 Phố Huế, Hà Nội',
                'delivery_phone' => '0945678901',
                'delivery_contact' => 'Vũ Thị F',
                'shipper_name' => 'Best Express',
                'tracking_number' => 'BE654987321',
                'status' => 'delivered',
                'notes' => 'Giao hàng đúng giờ',
                'created_by' => 2,
            ],
            [
                'id' => 7,
                'code' => 'DN-2024-007',
                'order_id' => 7,
                'customer_id' => 7,
                'branch_id' => 2,
                'warehouse_id' => 3,
                'delivery_date' => $now->toDateTimeString(),
                'delivery_address' => '258 Hai Bà Trưng, HCM',
                'delivery_phone' => '0956789012',
                'delivery_contact' => 'Đỗ Văn G',
                'shipper_name' => 'Kerry Express',
                'tracking_number' => 'KE987321654',
                'status' => 'preparing',
                'notes' => 'Đang chuẩn bị hàng',
                'created_by' => 1,
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