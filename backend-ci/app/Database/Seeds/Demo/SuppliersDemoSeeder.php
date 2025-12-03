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
                'name_vi' => 'Công ty TNHH Da Giày Việt Nam',
                'name_en' => 'Viet Nam Leather Co., Ltd',
                'email' => 'contact@dagiay.vn',
                'phone' => '024-3888-9999',
                'address' => '123 Đường Láng, Hà Nội',
                'tax_code' => '0123456789',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'code' => 'SUP-002',
                'name_vi' => 'Xưởng Sản Xuất Túi Xách Hồng Hà',
                'name_en' => 'Hong Ha Bag Workshop',
                'email' => 'hongha@tuixach.vn',
                'phone' => '024-3777-8888',
                'address' => '456 Phố Huế, Hà Nội',
                'tax_code' => '0987654321',
                'status' => 'active',
            ],
            [
                'id' => 3,
                'code' => 'SUP-003',
                'name_vi' => 'Công ty CP Phụ Kiện Thời Trang',
                'name_en' => 'Fashion Accessories JSC',
                'email' => 'info@phukien.com.vn',
                'phone' => '028-3666-7777',
                'address' => '789 Nguyễn Huệ, HCM',
                'tax_code' => '0111222333',
                'status' => 'active',
            ],
            [
                'id' => 4,
                'code' => 'SUP-004',
                'name_vi' => 'Nhà Máy Dệt May Tân Tiến',
                'name_en' => 'Tan Tien Textile Factory',
                'email' => 'sales@tantien.vn',
                'phone' => '0236-3555-6666',
                'address' => '321 Lê Duẩn, Đà Nẵng',
                'tax_code' => '0444555666',
                'status' => 'active',
            ],
            [
                'id' => 5,
                'code' => 'SUP-005',
                'name_vi' => 'Xưởng Gia Công Đồng Phát',
                'name_en' => 'Dong Phat Workshop',
                'email' => 'dongphat@workshop.vn',
                'phone' => '0292-3444-5555',
                'address' => '654 Đường 3/2, Cần Thơ',
                'tax_code' => '0777888999',
                'status' => 'active',
            ],
            [
                'id' => 6,
                'code' => 'SUP-006',
                'name_vi' => 'Công ty TNHH Vải Cao Cấp',
                'name_en' => 'Premium Fabric Co., Ltd',
                'email' => 'premium@fabric.vn',
                'phone' => '024-3333-4444',
                'address' => '987 Trần Hưng Đạo, Hà Nội',
                'tax_code' => '0222333444',
                'status' => 'active',
            ],
            [
                'id' => 7,
                'code' => 'SUP-007',
                'name_vi' => 'Nhà Cung Cấp Phụ Liệu Minh Anh',
                'name_en' => 'Minh Anh Materials',
                'email' => 'minhanh@materials.vn',
                'phone' => '028-3222-3333',
                'address' => '147 Lê Lợi, HCM',
                'tax_code' => '0555666777',
                'status' => 'active',
            ],
            [
                'id' => 8,
                'code' => 'SUP-008',
                'name_vi' => 'Xưởng Thêu Ren Hoa Mai',
                'name_en' => 'Hoa Mai Embroidery',
                'email' => 'hoamai@embroidery.vn',
                'phone' => '0225-3111-2222',
                'address' => '258 Lạch Tray, Hải Phòng',
                'tax_code' => '0888999000',
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
