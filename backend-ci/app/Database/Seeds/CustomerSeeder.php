<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('customers')->truncate();
        $now = date('Y-m-d H:i:s');

        $users = [
            ['Nguyễn Văn An', '0901234567', 'an.nguyen@example.com', 'Hà Nội'],
            ['Trần Thị Bình', '0902345678', 'binh.tran@example.com', 'TP.HCM'],
            ['Lê Hoàng Cường', '0903456789', 'cuong.le@example.com', 'Đà Nẵng'],
            ['Phạm Minh Dung', '0904567890', 'dung.pham@example.com', 'Hải Phòng'],
            ['Hoàng Văn Em', '0905678901', 'em.hoang@example.com', 'Cần Thơ'],
            ['Vũ Thị Phương', '0906789012', 'phuong.vu@example.com', 'Hà Nội'],
            ['Đặng Văn Giàu', '0907890123', 'giau.dang@example.com', 'TP.HCM'],
            ['Bùi Thị Hạnh', '0908901234', 'hanh.bui@example.com', 'Đà Nẵng'],
            ['Đỗ Văn Hùng', '0909012345', 'hung.do@example.com', 'Hải Phòng'],
            ['Ngô Thị Lan', '0910123456', 'lan.ngo@example.com', 'Hà Nội'],
        ];

        $customers = [];
        $idx = 1;
        foreach ($users as $u) {
            $code = 'KH' . str_pad($idx, 4, '0', STR_PAD_LEFT);
            $customers[] = [
                'id' => $idx,
                'name' => $u[0],
                'code' => $code,
                'email' => $u[2],
                'phone' => $u[1],
                'address' => 'Địa chỉ số ' . $idx . ', ' . $u[3],
                'province' => $u[3], // Schema has province, city is usually same
                'customer_type' => 'INDIVIDUAL',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $idx++;
        }

        $this->db->table('customers')->insertBatch($customers);
        $this->db->enableForeignKeyChecks();

        echo "✅ Seeded " . count($customers) . " Customers.\n";
    }
}
