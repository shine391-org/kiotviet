<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * SuppliersDemoSeeder - Demo suppliers for testing purchase workflows
 * 
 * @agent-seeder: Demo suppliers
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class SuppliersDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo suppliers...\n";
        
        $now = Time::now();

        $suppliers = [
            [
                'id' => 1,
                'code' => 'SUP-001',
                'name' => 'Công ty TNHH Da Giày Việt Nam',
                'email' => 'contact@dagiay.vn',
                'phone' => '024-3888-9999',
                'address' => '123 Đường Láng, Hà Nội',
                'tax_code' => '0123456789',
                'type' => 'company',
                'payment_terms' => 30,
                'status' => 'active',
            ],
            [
                'id' => 2,
                'code' => 'SUP-002',
                'name' => 'Xưởng Sản Xuất Túi Xách Hồng Hà',
                'email' => 'hongha@tuixach.vn',
                'phone' => '024-3777-8888',
                'address' => '456 Phố Huế, Hà Nội',
                'tax_code' => '0987654321',
                'type' => 'company',
                'payment_terms' => 15,
                'status' => 'active',
            ],
            [
                'id' => 3,
                'code' => 'SUP-003',
                'name' => 'Công ty CP Phụ Kiện Thời Trang',
                'email' => 'info@phukien.com.vn',
                'phone' => '028-3666-7777',
                'address' => '789 Nguyễn Huệ, HCM',
                'tax_code' => '0111222333',
                'type' => 'company',
                'payment_terms' => 45,
                'status' => 'active',
            ],
            [
                'id' => 4,
                'code' => 'SUP-004',
                'name' => 'Nhà Máy Dệt May Tân Tiến',
                'email' => 'sales@tantien.vn',
                'phone' => '0236-3555-6666',
                'address' => '321 Lê Duẩn, Đà Nẵng',
                'tax_code' => '0444555666',
                'type' => 'company',
                'payment_terms' => 30,
                'status' => 'active',
            ],
            [
                'id' => 5,
                'code' => 'SUP-005',
                'name' => 'Xưởng Gia Công Đồng Phát',
                'email' => 'dongphat@workshop.vn',
                'phone' => '0292-3444-5555',
                'address' => '654 Đường 3/2, Cần Thơ',
                'tax_code' => '0777888999',
                'type' => 'individual',
                'payment_terms' => 7,
                'status' => 'active',
            ],
            [
                'id' => 6,
                'code' => 'SUP-006',
                'name' => 'Công ty TNHH Vải Cao Cấp',
                'email' => 'premium@fabric.vn',
                'phone' => '024-3333-4444',
                'address' => '987 Trần Hưng Đạo, Hà Nội',
                'tax_code' => '0222333444',
                'type' => 'company',
                'payment_terms' => 60,
                'status' => 'active',
            ],
            [
                'id' => 7,
                'code' => 'SUP-007',
                'name' => 'Nhà Cung Cấp Phụ Liệu Minh Anh',
                'email' => 'minhanh@materials.vn',
                'phone' => '028-3222-3333',
                'address' => '147 Lê Lợi, HCM',
                'tax_code' => '0555666777',
                'type' => 'company',
                'payment_terms' => 30,
                'status' => 'active',
            ],
            [
                'id' => 8,
                'code' => 'SUP-008',
                'name' => 'Xưởng Thêu Ren Hoa Mai',
                'email' => 'hoamai@embroidery.vn',
                'phone' => '0225-3111-2222',
                'address' => '258 Lạch Tray, Hải Phòng',
                'tax_code' => '0888999000',
                'type' => 'individual',
                'payment_terms' => 15,
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            $supplier['created_at'] = $now;
            $supplier['updated_at'] = $now;
            $this->db->table('suppliers')->ignore(true)->insert($supplier);
        }

        echo "      ✓ Created " . count($suppliers) . " demo suppliers\n";
    }
}