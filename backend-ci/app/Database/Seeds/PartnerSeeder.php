<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('partners')->truncate();
        $now = date('Y-m-d H:i:s');
        
        // 1. Suppliers
        $suppliers = [
            ['Công ty May Việt Tiến', '0243111222', 'contact@viettien.com.vn', 'Hà Nội'],
            ['Công ty Giày Thượng Đình', '0243333444', 'sales@thuongdinh.com', 'Hà Nội'],
            ['NPP Thời Trang Owen', '0285555666', 'support@owen.vn', 'TP.HCM'],
        ];

        $partners = [];
        $idx = 1;
        foreach ($suppliers as $s) {
            $partners[] = [
                'id' => $idx,
                'code' => 'NCC' . str_pad($idx, 4, '0', STR_PAD_LEFT),
                'name' => $s[0],
                'type' => 'supplier', // Valid enum
                'contact_person' => 'Phòng Kinh Doanh',
                'phone' => $s[1],
                'email' => $s[2],
                'address' => $s[3],
                'city' => $s[3],
                'credit_limit' => 0,
                'debt_amount' => 0,
                'total_purchased' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $idx++;
        }

        $this->db->table('partners')->insertBatch($partners);
        $this->db->enableForeignKeyChecks();

        echo "✅ Seeded " . count($partners) . " Suppliers (Partners table).\n";
    }
}
