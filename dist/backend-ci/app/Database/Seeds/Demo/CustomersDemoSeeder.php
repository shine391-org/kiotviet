<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Seed 20 khách hàng demo đa dạng (cá nhân & công ty) cho môi trường dev.
 *
 * @agent-seeder: Customers demo data
 * @agent-pattern: Delete-by-code + insertBatch
 * @agent-reusable: MEDIUM
 */
class CustomersDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('customers')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $customers = [];
        $base = [
            'organization_id' => 1,
            'customer_group_id' => null,
            'gender' => null,
            'facebook' => null,
            'customer_type' => 'INDIVIDUAL',
            'company_name' => null,
            'tax_code' => null,
            'buyer_name' => null,
            'invoice_company_name' => null,
            'invoice_address' => null,
            'invoice_province' => null,
            'invoice_district' => null,
            'invoice_ward' => null,
            'invoice_email' => null,
            'invoice_phone' => null,
            'cccd_cmnd' => null,
            'id_number' => null,
            'bank_account' => null,
            'bank_name' => null,
            'notes' => null,
            'status' => 'ACTIVE',
            'created_by' => 1,
            'last_transaction_at' => null,
            'current_debt' => 0,
            'total_sales' => 0,
            'total_sales_net' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $demoRows = [
            ['id' => 2001, 'code' => 'CUST-DEMO-001', 'name' => 'Nguyễn Minh An', 'phone' => '0912000001', 'email' => 'an.demo@lano.local', 'address' => '12 Trần Hưng Đạo, Hà Nội', 'province' => 'Hà Nội', 'district' => 'Hoàn Kiếm', 'ward' => 'Hàng Bài', 'gender' => 'MALE'],
            ['id' => 2002, 'code' => 'CUST-DEMO-002', 'name' => 'Trần Thu Hà', 'phone' => '0912000002', 'email' => 'ha.demo@lano.local', 'address' => '89 Lý Thường Kiệt, Hà Nội', 'province' => 'Hà Nội', 'district' => 'Hoàn Kiếm', 'ward' => 'Cửa Nam', 'gender' => 'FEMALE'],
            ['id' => 2003, 'code' => 'CUST-DEMO-003', 'name' => 'Phạm Gia Bảo', 'phone' => '0912000003', 'email' => 'bao.demo@lano.local', 'address' => '22 Nguyễn Huệ, HCM', 'province' => 'Hồ Chí Minh', 'district' => 'Quận 1', 'ward' => 'Bến Nghé', 'gender' => 'MALE'],
            ['id' => 2004, 'code' => 'CUST-DEMO-004', 'name' => 'Lê Hồng Nhung', 'phone' => '0912000004', 'email' => 'nhung.demo@lano.local', 'address' => '35 Hai Bà Trưng, HCM', 'province' => 'Hồ Chí Minh', 'district' => 'Quận 1', 'ward' => 'Bến Thành', 'gender' => 'FEMALE'],
            ['id' => 2005, 'code' => 'CUST-DEMO-005', 'name' => 'Vũ Hoàng Long', 'phone' => '0912000005', 'email' => 'long.demo@lano.local', 'address' => '15 Nguyễn Tri Phương, Đà Nẵng', 'province' => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Thạch Thang', 'gender' => 'MALE'],
            ['id' => 2006, 'code' => 'CUST-DEMO-006', 'name' => 'Đặng Bích Trâm', 'phone' => '0912000006', 'email' => 'tram.demo@lano.local', 'address' => '101 Võ Văn Tần, HCM', 'province' => 'Hồ Chí Minh', 'district' => 'Quận 3', 'ward' => '6', 'gender' => 'FEMALE'],
            ['id' => 2007, 'code' => 'CUST-DEMO-007', 'name' => 'Huỳnh Tuấn Kiệt', 'phone' => '0912000007', 'email' => 'kiet.demo@lano.local', 'address' => '45 Trần Phú, Nha Trang', 'province' => 'Khánh Hòa', 'district' => 'Nha Trang', 'ward' => 'Lộc Thọ', 'gender' => 'MALE'],
            ['id' => 2008, 'code' => 'CUST-DEMO-008', 'name' => 'Lý Thu Uyên', 'phone' => '0912000008', 'email' => 'uyen.demo@lano.local', 'address' => '68 Lê Lợi, Huế', 'province' => 'Thừa Thiên Huế', 'district' => 'Huế', 'ward' => 'Phú Hội', 'gender' => 'FEMALE'],
            ['id' => 2009, 'code' => 'CUST-DEMO-009', 'name' => 'Ngô Nhật Anh', 'phone' => '0912000009', 'email' => 'nha.demo@lano.local', 'address' => '12 Nguyễn Văn Linh, Đà Nẵng', 'province' => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Nam Dương', 'gender' => 'MALE'],
            ['id' => 2010, 'code' => 'CUST-DEMO-010', 'name' => 'Tạ Kim Yến', 'phone' => '0912000010', 'email' => 'yen.demo@lano.local', 'address' => '99 Phan Chu Trinh, Đà Nẵng', 'province' => 'Đà Nẵng', 'district' => 'Hải Châu', 'ward' => 'Hải Châu 1', 'gender' => 'FEMALE'],

            // Công ty / hộ kinh doanh
            ['id' => 2011, 'code' => 'CUST-DEMO-011', 'name' => 'Công ty Ánh Dương', 'customer_type' => 'COMPANY', 'company_name' => 'Công ty TNHH Ánh Dương', 'tax_code' => '0101234567', 'phone' => '0912000011', 'email' => 'contact@anhduong.vn', 'address' => '11 Duy Tân, Cầu Giấy, Hà Nội', 'province' => 'Hà Nội', 'district' => 'Cầu Giấy', 'ward' => 'Dịch Vọng', 'invoice_company_name' => 'Công ty TNHH Ánh Dương', 'invoice_address' => '11 Duy Tân, Hà Nội'],
            ['id' => 2012, 'code' => 'CUST-DEMO-012', 'name' => 'CTCP Gỗ Xanh', 'customer_type' => 'COMPANY', 'company_name' => 'CTCP Gỗ Xanh', 'tax_code' => '0312345678', 'phone' => '0912000012', 'email' => 'ke.toan@goxanh.vn', 'address' => '45 Pasteur, Quận 1, HCM', 'province' => 'Hồ Chí Minh', 'district' => 'Quận 1', 'ward' => 'Bến Nghé', 'invoice_company_name' => 'CTCP Gỗ Xanh', 'invoice_address' => '45 Pasteur, Quận 1'],
            ['id' => 2013, 'code' => 'CUST-DEMO-013', 'name' => 'Hộ KD Minh Quân', 'customer_type' => 'HOUSEHOLD', 'company_name' => 'Hộ KD Minh Quân', 'tax_code' => '4200123456', 'phone' => '0912000013', 'email' => 'minhquan@hkd.vn', 'address' => '22 Trần Phú, Nha Trang', 'province' => 'Khánh Hòa', 'district' => 'Nha Trang', 'ward' => 'Vạn Thạnh'],
            ['id' => 2014, 'code' => 'CUST-DEMO-014', 'name' => 'Công ty Vận Tải Nhanh', 'customer_type' => 'COMPANY', 'company_name' => 'Công ty Vận Tải Nhanh', 'tax_code' => '0109988776', 'phone' => '0912000014', 'email' => 'sale@vantaNhanh.vn', 'address' => '88 Kim Mã, Ba Đình, Hà Nội', 'province' => 'Hà Nội', 'district' => 'Ba Đình', 'ward' => 'Kim Mã'],
            ['id' => 2015, 'code' => 'CUST-DEMO-015', 'name' => 'CTY Thiết Kế Mộc', 'customer_type' => 'COMPANY', 'company_name' => 'CTY Thiết Kế Mộc', 'tax_code' => '0311122233', 'phone' => '0912000015', 'email' => 'info@thietkemoc.vn', 'address' => '12 Nguyễn Trãi, Quận 5, HCM', 'province' => 'Hồ Chí Minh', 'district' => 'Quận 5', 'ward' => '7'],

            // Khách lẻ thêm để đủ 20
            ['id' => 2016, 'code' => 'CUST-DEMO-016', 'name' => 'Trịnh Quốc Thái', 'phone' => '0912000016', 'email' => 'thai.demo@lano.local', 'address' => '14 Lê Duẩn, Hà Nội', 'province' => 'Hà Nội', 'district' => 'Ba Đình', 'ward' => 'Điện Biên'],
            ['id' => 2017, 'code' => 'CUST-DEMO-017', 'name' => 'Đỗ Hồng Ngọc', 'phone' => '0912000017', 'email' => 'ngoc.demo@lano.local', 'address' => '7 Nguyễn Văn Cừ, Hạ Long', 'province' => 'Quảng Ninh', 'district' => 'Hạ Long', 'ward' => 'Bạch Đằng'],
            ['id' => 2018, 'code' => 'CUST-DEMO-018', 'name' => 'La Mỹ Duyên', 'phone' => '0912000018', 'email' => 'duyen.demo@lano.local', 'address' => '155 Lạch Tray, Hải Phòng', 'province' => 'Hải Phòng', 'district' => 'Ngô Quyền', 'ward' => 'Lạch Tray'],
            ['id' => 2019, 'code' => 'CUST-DEMO-019', 'name' => 'Đinh Mạnh Cường', 'phone' => '0912000019', 'email' => 'cuong.demo@lano.local', 'address' => '18 Lê Lợi, Vinh', 'province' => 'Nghệ An', 'district' => 'Vinh', 'ward' => 'Hưng Bình'],
            ['id' => 2020, 'code' => 'CUST-DEMO-020', 'name' => 'Phùng Thanh Mai', 'phone' => '0912000020', 'email' => 'mai.demo@lano.local', 'address' => '3 Hùng Vương, Huế', 'province' => 'Thừa Thiên Huế', 'district' => 'Huế', 'ward' => 'Phú Nhuận'],
        ];

        foreach ($demoRows as $row) {
            $customers[] = array_merge($base, $row);
        }

        // Dọn dữ liệu demo cũ trước khi chèn mới
        $this->db->table('customers')
            ->groupStart()
            ->like('code', 'CUST-DEMO-', 'after')
            ->groupEnd()
            ->delete();

        $this->db->table('customers')->ignore(true)->insertBatch($customers);
    }
}
