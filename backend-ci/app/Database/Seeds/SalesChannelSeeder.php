<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SalesChannelSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $channels = [
            [
                'code' => 'pos',
                'name' => 'Bán tại quầy',
                'description' => 'Bán hàng trực tiếp tại cửa hàng',
                'icon' => 'store',
                'color' => '#4CAF50',
                'is_active' => 1,
                'is_default' => 1,
                'sort_order' => 1,
                'settings' => json_encode(['allow_debt' => true, 'require_customer' => false]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'delivery',
                'name' => 'Giao hàng',
                'description' => 'Đơn hàng giao đến khách',
                'icon' => 'truck',
                'color' => '#2196F3',
                'is_active' => 1,
                'is_default' => 0,
                'sort_order' => 2,
                'settings' => json_encode(['require_address' => true, 'require_phone' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'online',
                'name' => 'Bán online',
                'description' => 'Đơn từ website, app',
                'icon' => 'globe',
                'color' => '#9C27B0',
                'is_active' => 1,
                'is_default' => 0,
                'sort_order' => 3,
                'settings' => json_encode(['auto_confirm' => false]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'marketplace',
                'name' => 'Sàn TMĐT',
                'description' => 'Shopee, Lazada, Tiki...',
                'icon' => 'shopping-bag',
                'color' => '#FF5722',
                'is_active' => 1,
                'is_default' => 0,
                'sort_order' => 4,
                'settings' => json_encode(['platforms' => ['shopee', 'lazada', 'tiki']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('sales_channels')->insertBatch($channels);
        echo "SalesChannelSeeder: Seeded " . count($channels) . " sales channels.\n";
    }
}
