<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seed sample bank accounts for POS payments
 */
class BankAccountSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $accounts = [
            [
                'bank_name' => 'BIDV',
                'bank_code' => 'BIDV',
                'account_number' => '2206331765',
                'account_name' => 'NGUYEN THI PHUONG ANH',
                'branch_name' => 'Chi nhánh Hà Nội',
                'branch_id' => 1,
                'is_default' => 1,
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bank_name' => 'Vietcombank',
                'bank_code' => 'VCB',
                'account_number' => '1234567890',
                'account_name' => 'NGUYEN THI PHUONG ANH',
                'branch_name' => 'Chi nhánh Cầu Giấy',
                'branch_id' => 1,
                'is_default' => 0,
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bank_name' => 'Techcombank',
                'bank_code' => 'TCB',
                'account_number' => '19035678901234',
                'account_name' => 'NGUYEN THI PHUONG ANH',
                'branch_name' => 'Chi nhánh Đống Đa',
                'branch_id' => null,
                'is_default' => 0,
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bank_name' => 'MB Bank',
                'bank_code' => 'MB',
                'account_number' => '0981234567890',
                'account_name' => 'NGUYEN THI PHUONG ANH',
                'branch_name' => 'Chi nhánh HCM',
                'branch_id' => 2,
                'is_default' => 1,
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bank_name' => 'VPBank',
                'bank_code' => 'VPB',
                'account_number' => '123456789012',
                'account_name' => 'NGUYEN THI PHUONG ANH',
                'branch_name' => null,
                'branch_id' => null,
                'is_default' => 0,
                'is_active' => 1,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('bank_accounts')->insertBatch($accounts);
        echo "BankAccountSeeder: Seeded " . count($accounts) . " bank accounts.\n";
    }
}
