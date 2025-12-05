<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CashTransactionDemoSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $rows = [
            [
                'type' => 'RECEIPT',
                'amount' => 1500000,
                'category' => 'sales',
                'payment_method' => 'cash',
                'status' => 'approved',
                'account_name' => 'Tiền mặt quỹ',
                'bank_account' => null,
                'description' => 'Thu tiền đơn hàng HD031574',
                'reference_type' => 'order',
                'reference_id' => 1,
                'reference_code' => 'HD031574',
                'branch_id' => 1,
                'created_by' => 1,
                'created_by_name' => 'nhung',
                'staff_name' => 'nhung',
                'payer_code' => 'KH024942',
                'payer_name' => 'a Hòa',
                'payer_phone' => '0938706989',
                'payer_address' => 'Lano - HN',
                'transfer_note' => null,
                'transaction_date' => date('Y-m-d'),
                'note' => 'Khách thanh toán đủ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'PAYMENT',
                'amount' => 520000,
                'category' => 'purchase',
                'payment_method' => 'bank',
                'status' => 'approved',
                'account_name' => 'VCB 9988',
                'bank_account' => '9704-0000-9988',
                'description' => 'Chi trả PO NCC',
                'reference_type' => 'purchase_order',
                'reference_id' => 2,
                'reference_code' => 'PO1002',
                'branch_id' => 1,
                'created_by' => 2,
                'created_by_name' => 'trung',
                'staff_name' => 'trung',
                'payer_code' => 'NCC01',
                'payer_name' => 'Nhà cung cấp A',
                'payer_phone' => '0988111222',
                'payer_address' => 'Hà Nội',
                'transfer_note' => 'Thanh toán PO1002',
                'transaction_date' => date('Y-m-d'),
                'note' => 'Chuyển khoản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'PAYMENT',
                'amount' => 300000,
                'category' => 'shipping_fee',
                'payment_method' => 'ewallet',
                'status' => 'approved',
                'account_name' => 'Momo cửa hàng',
                'bank_account' => '0912000123',
                'description' => 'Trả phí ship đối soát 5 đơn',
                'reference_type' => 'shipping_settlement',
                'reference_id' => 10,
                'reference_code' => 'SS-10',
                'branch_id' => 1,
                'created_by' => 1,
                'created_by_name' => 'nhung',
                'staff_name' => 'nhung',
                'payer_code' => 'SHIP-01',
                'payer_name' => 'ĐVVC Nhanh',
                'payer_phone' => '0909888777',
                'payer_address' => 'HCM',
                'transfer_note' => 'Phí ship tuần 47',
                'transaction_date' => date('Y-m-d'),
                'note' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('cash_transactions')->truncate();
        $this->db->table('cash_transactions')->insertBatch($rows);
    }
}
