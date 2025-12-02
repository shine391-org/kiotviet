<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * Seed default payment methods.
 *
 * @agent-seeder: Payment methods
 * @agent-pattern: Master data seeder
 */
class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now();
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Tiền mặt',
                'description' => 'Thanh toán bằng tiền mặt tại cửa hàng',
                'is_active' => 1,
                'display_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BANK_TRANSFER',
                'name' => 'Chuyển khoản ngân hàng',
                'description' => 'Chuyển khoản qua tài khoản ngân hàng',
                'is_active' => 1,
                'display_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CARD',
                'name' => 'Thẻ tín dụng/ghi nợ',
                'description' => 'Thanh toán bằng thẻ Visa/Mastercard/JCB',
                'is_active' => 1,
                'display_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'COD',
                'name' => 'Thu hộ (COD)',
                'description' => 'Thanh toán khi nhận hàng. Phí COD: 15,000đ',
                'is_active' => 1,
                'display_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'E_WALLET',
                'name' => 'Ví điện tử',
                'description' => 'Thanh toán qua MoMo, ZaloPay, VNPay',
                'is_active' => 1,
                'display_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('payment_methods')->ignore(true)->insertBatch($methods);
    }
}
