<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Seeder 20 đơn hàng demo kèm line items + status logs cho FE/QA.
 *
 * @agent-seeder: Orders demo data
 * @agent-pattern: Delete-by-prefix + per-row insert to map IDs
 * @agent-reusable: MEDIUM
 */
class OrdersDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $today = new \DateTimeImmutable('today');

        // Xóa dữ liệu demo cũ
        $existing = $this->db->table('orders')
            ->select('id')
            ->like('order_number', 'DH-DEMO-', 'after')
            ->get()
            ->getResultArray();

        $existingIds = array_column($existing, 'id');
        if (! empty($existingIds)) {
            if ($this->db->tableExists('order_items')) {
                $this->db->table('order_items')->whereIn('order_id', $existingIds)->delete();
            }
            if ($this->db->tableExists('order_status_logs')) {
                $this->db->table('order_status_logs')->whereIn('order_id', $existingIds)->delete();
            }
            $this->db->table('orders')->whereIn('id', $existingIds)->delete();
        }

        // Mẫu đơn hàng (20 bản ghi)
        $orders = [
            ['order_number' => 'DH-DEMO-001', 'customer_id' => 2001, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'unpaid',  'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 9000000,  'discount_total' => 0,     'shipping_fee' => 0,     'total' => 9000000,  'paid_amount' => 0,        'order_date' => $today->modify('-30 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-002', 'customer_id' => 2002, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'partial', 'order_type' => 'offline', 'payment_method' => 'bank_transfer', 'subtotal' => 2950000,  'discount_total' => 0,     'shipping_fee' => 55000, 'total' => 3005000,  'paid_amount' => 1500000, 'order_date' => $today->modify('-28 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-003', 'customer_id' => 2003, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'cod',           'subtotal' => 1550000,  'discount_total' => 0,     'shipping_fee' => 35000, 'total' => 1585000,  'paid_amount' => 700000,  'order_date' => $today->modify('-25 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-004', 'customer_id' => 2004, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'partial', 'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 2850000,  'discount_total' => 0,     'shipping_fee' => 0,     'total' => 2850000,  'paid_amount' => 1000000, 'order_date' => $today->modify('-23 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-005', 'customer_id' => 2005, 'branch_id' => 1, 'status' => 'completed',  'payment_status' => 'paid',    'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 950000,   'discount_total' => 0,     'shipping_fee' => 25000, 'total' => 975000,   'paid_amount' => 975000,  'order_date' => $today->modify('-20 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-006', 'customer_id' => 2006, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'cod',           'subtotal' => 900000,   'discount_total' => 0,     'shipping_fee' => 30000, 'total' => 930000,   'paid_amount' => 900000,  'order_date' => $today->modify('-18 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-007', 'customer_id' => 2007, 'branch_id' => 1, 'status' => 'draft',      'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'bank_transfer', 'subtotal' => 1700000,  'discount_total' => 0,     'shipping_fee' => 0,     'total' => 1700000,  'paid_amount' => 700000,  'order_date' => $today->modify('-18 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-008', 'customer_id' => 2008, 'branch_id' => 2, 'status' => 'processing', 'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'ewallet',       'subtotal' => 2200000,  'discount_total' => 120000,'shipping_fee' => 40000, 'total' => 2120000,  'paid_amount' => 1000000, 'order_date' => $today->modify('-15 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-009', 'customer_id' => 2009, 'branch_id' => 2, 'status' => 'processing', 'payment_status' => 'unpaid',  'order_type' => 'online',  'payment_method' => 'cod',           'subtotal' => 1250000,  'discount_total' => 0,     'shipping_fee' => 35000, 'total' => 1285000,  'paid_amount' => 0,        'order_date' => $today->modify('-14 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-010', 'customer_id' => 2010, 'branch_id' => 2, 'status' => 'shipping',   'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'cod',           'subtotal' => 1600000,  'discount_total' => 0,     'shipping_fee' => 25000, 'total' => 1625000,  'paid_amount' => 700000,  'order_date' => $today->modify('-12 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-011', 'customer_id' => 2011, 'branch_id' => 1, 'status' => 'shipping',   'payment_status' => 'partial', 'order_type' => 'offline', 'payment_method' => 'bank_transfer', 'subtotal' => 5200000,  'discount_total' => 200000,'shipping_fee' => 50000, 'total' => 5050000,  'paid_amount' => 2500000, 'order_date' => $today->modify('-10 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-012', 'customer_id' => 2012, 'branch_id' => 1, 'status' => 'shipping',   'payment_status' => 'paid',    'order_type' => 'online',  'payment_method' => 'bank_transfer', 'subtotal' => 6800000,  'discount_total' => 300000,'shipping_fee' => 60000, 'total' => 6560000,  'paid_amount' => 6560000, 'order_date' => $today->modify('-9 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-013', 'customer_id' => 2013, 'branch_id' => 2, 'status' => 'delivered',  'payment_status' => 'paid',    'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 3200000,  'discount_total' => 0,     'shipping_fee' => 0,     'total' => 3200000,  'paid_amount' => 3200000, 'order_date' => $today->modify('-8 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-014', 'customer_id' => 2014, 'branch_id' => 2, 'status' => 'delivered',  'payment_status' => 'paid',    'order_type' => 'offline', 'payment_method' => 'bank_transfer', 'subtotal' => 5400000,  'discount_total' => 250000,'shipping_fee' => 55000, 'total' => 5205000,  'paid_amount' => 5205000, 'order_date' => $today->modify('-7 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-015', 'customer_id' => 2015, 'branch_id' => 1, 'status' => 'completed',  'payment_status' => 'paid',    'order_type' => 'offline', 'payment_method' => 'bank_transfer', 'subtotal' => 7300000,  'discount_total' => 300000,'shipping_fee' => 0,     'total' => 7000000,  'paid_amount' => 7000000, 'order_date' => $today->modify('-6 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-016', 'customer_id' => 2016, 'branch_id' => 1, 'status' => 'completed',  'payment_status' => 'paid',    'order_type' => 'online',  'payment_method' => 'ewallet',       'subtotal' => 2100000,  'discount_total' => 0,     'shipping_fee' => 30000, 'total' => 2130000,  'paid_amount' => 2130000, 'order_date' => $today->modify('-5 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-017', 'customer_id' => 2017, 'branch_id' => 1, 'status' => 'completed',  'payment_status' => 'paid',    'order_type' => 'online',  'payment_method' => 'cod',           'subtotal' => 1950000,  'discount_total' => 50000, 'shipping_fee' => 25000, 'total' => 1925000,  'paid_amount' => 1925000, 'order_date' => $today->modify('-4 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-018', 'customer_id' => 2018, 'branch_id' => 2, 'status' => 'completed',  'payment_status' => 'paid',    'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 2550000,  'discount_total' => 0,     'shipping_fee' => 0,     'total' => 2550000,  'paid_amount' => 2550000, 'order_date' => $today->modify('-3 days')->format('Y-m-d H:i:s')],
            ['order_number' => 'DH-DEMO-019', 'customer_id' => 2019, 'branch_id' => 2, 'status' => 'cancelled',  'payment_status' => 'unpaid',  'order_type' => 'offline', 'payment_method' => 'cash',          'subtotal' => 1800000,  'discount_total' => 0,     'shipping_fee' => 20000, 'total' => 1820000,  'paid_amount' => 0,        'order_date' => $today->modify('-2 days')->format('Y-m-d H:i:s'), 'cancellation_reason' => 'Khách đổi ý'],
            ['order_number' => 'DH-DEMO-020', 'customer_id' => 2020, 'branch_id' => 1, 'status' => 'cancelled',  'payment_status' => 'partial', 'order_type' => 'online',  'payment_method' => 'ewallet',       'subtotal' => 1450000,  'discount_total' => 0,     'shipping_fee' => 30000, 'total' => 1480000,  'paid_amount' => 300000,  'order_date' => $today->modify('-1 days')->format('Y-m-d H:i:s'), 'cancellation_reason' => 'Hết hàng'],
        ];

        $orderIdMap = [];
        foreach ($orders as $order) {
            $order['debt_amount'] = $order['total'] - $order['paid_amount'];
            $order['is_paid'] = $order['paid_amount'] >= $order['total'] ? 1 : 0;
            $order['shipping_name'] = $order['shipping_name'] ?? 'Kho Lano';
            $order['shipping_phone'] = $order['shipping_phone'] ?? '0912000999';
            $order['shipping_address'] = $order['shipping_address'] ?? 'Kho tổng Lano';
            $order['shipping_city'] = $order['shipping_city'] ?? 'Hà Nội';
            $order['shipping_district'] = $order['shipping_district'] ?? 'Hoàn Kiếm';
            $order['shipping_ward'] = $order['shipping_ward'] ?? 'Hàng Bài';
            $order['created_at'] = $order['created_at'] ?? $order['order_date'];
            $order['updated_at'] = $order['updated_at'] ?? $order['order_date'];

            $this->db->table('orders')->insert($order);
            $id = (int) $this->db->insertID();
            $orderIdMap[$order['order_number']] = $id;
        }

        if ($this->db->tableExists('order_items')) {
            $items = $this->buildOrderItems($orderIdMap, $now);
            if (! empty($items)) {
                $this->db->table('order_items')->insertBatch($items);
            }
        }

        if ($this->db->tableExists('order_status_logs')) {
            $logs = $this->buildStatusLogs($orderIdMap, $orders, $now);
            if (! empty($logs)) {
                $this->db->table('order_status_logs')->insertBatch($logs);
            }
        }
    }

    /**
     * Tạo line items cho từng đơn, tham chiếu product/variant demo.
     */
    private function buildOrderItems(array $orderIdMap, string $now): array
    {
        $items = [];
        $line = function (string $orderNumber, int $productId, ?int $variantId, int $qty, float $price, ?int $priceListId = null, ?string $priceListName = null) use (&$items, $orderIdMap, $now) {
            if (! isset($orderIdMap[$orderNumber])) {
                return;
            }
            $items[] = [
                'order_id' => $orderIdMap[$orderNumber],
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'base_price' => $price,
                'final_price' => $price,
                'price_list_id' => $priceListId,
                'price_list_name' => $priceListName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        // Sử dụng sản phẩm có biến thể (501/502/503)
        $line('DH-DEMO-001', 501, 7001, 5, 450000);
        $line('DH-DEMO-001', 502, null, 2, 1320000);

        $line('DH-DEMO-002', 501, 7001, 1, 7500000);
        $line('DH-DEMO-002', 503, null, 1, 1500000);

        $line('DH-DEMO-003', 501, 7001, 3, 450000);

        $line('DH-DEMO-004', 502, null, 2, 1250000);

        $line('DH-DEMO-005', 502, null, 1, 1950000);

        $line('DH-DEMO-006', 501, 7001, 2, 450000);

        $line('DH-DEMO-007', 502, null, 1, 1350000);

        $line('DH-DEMO-008', 501, 7001, 2, 450000, 1, 'VIP 20%');
        $line('DH-DEMO-008', 503, null, 1, 1200000, 1, 'VIP 20%');

        $line('DH-DEMO-009', 502, null, 1, 1360000);

        $line('DH-DEMO-010', 501, 7001, 2, 450000);

        $line('DH-DEMO-011', 502, null, 3, 1280000);

        $line('DH-DEMO-012', 501, 7002, 2, 5250000, 3, 'Flash Sale 30%');

        $line('DH-DEMO-013', 501, 7001, 2, 450000);

        $line('DH-DEMO-014', 502, null, 2, 1280000);

        $line('DH-DEMO-015', 501, 7001, 1, 7500000);
        $line('DH-DEMO-015', 501, 7001, 2, 450000);

        $line('DH-DEMO-016', 503, null, 2, 1200000);

        $line('DH-DEMO-017', 501, 7001, 1, 450000);
        $line('DH-DEMO-017', 502, null, 1, 1250000);

        $line('DH-DEMO-018', 502, null, 1, 1320000);
        $line('DH-DEMO-018', 501, 7001, 1, 450000);

        $line('DH-DEMO-019', 501, 7001, 2, 450000);

        $line('DH-DEMO-020', 502, null, 1, 1280000);

        return $items;
    }

    /**
     * Tạo status logs tối thiểu cho từng đơn.
     */
    private function buildStatusLogs(array $orderIdMap, array $orders, string $now): array
    {
        $logs = [];
        $statusMap = [
            'draft'      => ['created'],
            'processing' => ['created', 'confirmed'],
            'shipping'   => ['created', 'confirmed', 'shipping'],
            'delivered'  => ['created', 'confirmed', 'shipping', 'delivered'],
            'completed'  => ['created', 'confirmed', 'shipping', 'delivered', 'completed'],
            'cancelled'  => ['created', 'cancelled'],
        ];

        foreach ($orders as $order) {
            $orderId = $orderIdMap[$order['order_number']] ?? null;
            if (! $orderId) {
                continue;
            }
            $statuses = $statusMap[$order['status']] ?? ['created'];
            $prev = 'none';
            foreach ($statuses as $step) {
                $logs[] = [
                    'order_id' => $orderId,
                    'from_status' => $prev,
                    'to_status' => $step,
                    'notes' => 'Auto demo log',
                    'changed_by' => 1,
                    'changed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $prev = $step;
            }
        }

        return $logs;
    }
}
