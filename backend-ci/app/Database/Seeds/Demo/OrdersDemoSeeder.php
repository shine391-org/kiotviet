<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use DateTimeImmutable;

/**
 * Seeder 20 đơn hàng demo kèm line items + status logs cho FE/QA.
 *
 * @agent-seeder: Orders demo data
 * @agent-pattern: Delete-by-prefix + per-row insert to map IDs
 * @agent-reusable: MEDIUM
 */
class OrdersDemoSeeder extends Seeder
{
    private array $priceMap = [];
    private array $customerMap = [];
    private array $orderItems = [];
    private array $orderLogs = [];
    private array $orderPayments = [];
    private array $vatTemplateMap = [
        '0.00' => 9001,
        '0.05' => 9002,
        '0.10' => 9003,
    ];

    public function run(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $this->priceMap = $this->loadPriceMap();
        $this->customerMap = $this->loadCustomers();

        $this->cleanupExistingOrders();

        $orders = $this->buildOrders();
        $orderIdMap = $this->insertOrders($orders);
        $this->insertOrderPayments($orderIdMap);
        $this->insertOrderItems($orderIdMap);
        $this->insertStatusLogs($orderIdMap);
    }

    private function buildOrders(): array
    {
        $today = new DateTimeImmutable('today');
        $orders = [];
        foreach ($this->orderDefinitions() as $index => $config) {
            $orderDate = $today->modify('-' . (30 - $index) . ' days');
            $items = $this->buildItems($config['order_number'], $config['items'], $orderDate);
            $timeline = $this->buildTimeline($config['order_number'], $config['status'], $orderDate);
            $shippingFee = (float) ($config['shipping_fee'] ?? 0);
            $vatRate = $this->vatRateForIndex($index);
            $taxAmount = $this->calculateVat($items['final_total'], $shippingFee, $vatRate);
            $total = round($items['final_total'] + $shippingFee + $taxAmount, 2);
            $paidAmount = $this->calculatePaidAmount($total, $config['paid_ratio'] ?? 0);
            $paymentMethod = $this->normalizeMethod($config['payment_method']);
            $shipping = $this->shippingInfo($config['customer_id']);

            $this->buildPayments($config['order_number'], $paymentMethod, $paidAmount, $orderDate);

            $orders[] = [
                'order_number' => $config['order_number'],
                'customer_id' => $config['customer_id'],
                'customer_group_id' => null,
                'branch_id' => $config['branch_id'],
                'order_date' => $orderDate->format('Y-m-d'),
                'order_type' => $config['order_type'],
                'payment_method' => $paymentMethod,
                'status' => $config['status'],
                'subtotal' => $items['subtotal'],
                'discount_total' => $items['discount_total'],
                'tax_template_id' => $this->templateIdForRate($vatRate),
                'tax_total' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'debt_amount' => max($total - $paidAmount, 0),
                'is_paid' => $paidAmount + 0.01 >= $total ? 1 : 0,
                'applied_price_list_id' => null,
                'shipping_name' => $shipping['name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_address' => $shipping['address'],
                'shipping_city' => $shipping['city'],
                'shipping_district' => $shipping['district'],
                'shipping_ward' => $shipping['ward'],
                'notes' => $config['notes'] ?? null,
                'payment_status' => $this->resolvePaymentStatus($paidAmount, $total),
                'cancellation_reason' => $config['cancellation_reason'] ?? null,
                'rounding_adjustment' => 0,
            ] + $timeline;
        }

        return $orders;
    }

    private function insertOrders(array $orders): array
    {
        $orderIdMap = [];
        foreach ($orders as $order) {
            $result = $this->db->table('orders')->insert($order);
            $insertId = (int) $this->db->insertID();
            if ($result === false || $insertId === 0) {
                continue;
            }
            $orderIdMap[$order['order_number']] = $insertId;
        }

        return $orderIdMap;
    }

    private function insertOrderPayments(array $orderIdMap): void
    {
        if (! $this->db->tableExists('order_payments') || empty($orderIdMap)) {
            return;
        }

        $rows = [];
        foreach ($this->orderPayments as $orderNumber => $payments) {
            $orderId = $orderIdMap[$orderNumber] ?? null;
            if (! $orderId) {
                continue;
            }
            foreach ($payments as $payment) {
                $rows[] = [
                    'order_id' => $orderId,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'paid_at' => $payment['paid_at'],
                    'created_at' => $payment['created_at'],
                    'updated_at' => $payment['updated_at'],
                ];
            }
        }

        if (! empty($rows)) {
            $this->db->table('order_payments')->insertBatch($rows);
        }
    }

    private function insertOrderItems(array $orderIdMap): void
    {
        if (! $this->db->tableExists('order_items') || empty($orderIdMap)) {
            return;
        }
        $rows = [];
        foreach ($this->orderItems as $orderNumber => $itemRows) {
            $orderId = $orderIdMap[$orderNumber] ?? null;
            if (! $orderId) {
                continue;
            }
            foreach ($itemRows as $row) {
                $row['order_id'] = $orderId;
                $rows[] = $row;
            }
        }

        if (! empty($rows)) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            $this->db->table('order_items')->insertBatch($rows);
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function insertStatusLogs(array $orderIdMap): void
    {
        if (! $this->db->tableExists('order_status_logs') || empty($orderIdMap)) {
            return;
        }

        $rows = [];
        foreach ($this->orderLogs as $orderNumber => $logs) {
            $orderId = $orderIdMap[$orderNumber] ?? null;
            if (! $orderId) {
                continue;
            }
            foreach ($logs as $log) {
                $rows[] = [
                    'order_id' => $orderId,
                    'from_status' => $log['from_status'],
                    'to_status' => $log['to_status'],
                    'notes' => $log['notes'],
                    'changed_by' => $log['changed_by'],
                    'changed_at' => $log['changed_at'],
                    'created_at' => $log['created_at'],
                    'updated_at' => $log['updated_at'],
                ];
            }
        }

        if (! empty($rows)) {
            $this->db->table('order_status_logs')->insertBatch($rows);
        }
    }

    private function buildItems(string $orderNumber, array $items, DateTimeImmutable $orderDate): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $finalTotal = 0;
        $firstPriceListId = null;
        $firstPriceListName = null;
        $createdAt = $orderDate->setTime(10, 0)->format('Y-m-d H:i:s');

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $qty = (float) $item['quantity'];
            $basePrice = $this->basePrice($productId, $variantId);
            $priceProfile = $item['pricing'] ?? 'standard';
            $priceListId = null;
            $priceListName = null;
            $finalPrice = $basePrice;

            if ($priceProfile === 'vip20') {
                $finalPrice = round($basePrice * 0.8, 2);
                $priceListId = 1;
                $priceListName = 'VIP 20%';
            } elseif ($priceProfile === 'flash30') {
                $finalPrice = round($basePrice * 0.7, 2);
                $priceListId = 3;
                $priceListName = 'Flash Sale 30%';
            } elseif ($priceProfile === 'discount10') {
                $finalPrice = round($basePrice * 0.9, 2);
            }

            $lineBase = round($basePrice * $qty, 2);
            $lineFinal = round($finalPrice * $qty, 2);
            $lineDiscount = round($lineBase - $lineFinal, 2);

            $subtotal += $lineBase;
            $discountTotal += max($lineDiscount, 0);
            $finalTotal += $lineFinal;

            if (! $firstPriceListId && $priceListId) {
                $firstPriceListId = $priceListId;
                $firstPriceListName = $priceListName;
            }

            $this->orderItems[$orderNumber][] = [
                'order_id' => null,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'price_list_id' => $priceListId,
                'price_list_name' => $priceListName,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'final_total' => round($finalTotal, 2),
            'price_list_id' => $firstPriceListId,
            'price_list_name' => $firstPriceListName,
        ];
    }

    private function buildTimeline(string $orderNumber, string $status, DateTimeImmutable $orderDate): array
    {
        $paths = [
            'draft' => ['created'],
            'processing' => ['created', 'confirmed', 'processing'],
            'shipping' => ['created', 'confirmed', 'processing', 'shipping'],
            'completed' => ['created', 'confirmed', 'processing', 'shipping', 'delivered', 'completed'],
            'cancelled' => ['created', 'cancelled'],
        ];
        $steps = $paths[$status] ?? ['created'];
        $timeMap = [];
        $base = $orderDate->setTime(10, 0);
        foreach ($steps as $index => $step) {
            $timeMap[$step] = $base->modify('+' . $index . ' hours');
        }
        $lastTime = end($timeMap);
        $this->orderLogs[$orderNumber] = $this->buildLogs($orderNumber, $steps, $timeMap);

        return [
            'created_at' => $timeMap[$steps[0]]->format('Y-m-d H:i:s'),
            'updated_at' => $lastTime ? $lastTime->format('Y-m-d H:i:s') : $orderDate->setTime(10, 0)->format('Y-m-d H:i:s'),
            'confirmed_at' => isset($timeMap['confirmed']) ? $timeMap['confirmed']->format('Y-m-d H:i:s') : null,
            'processing_at' => isset($timeMap['processing']) ? $timeMap['processing']->format('Y-m-d H:i:s') : null,
            'shipping_at' => isset($timeMap['shipping']) ? $timeMap['shipping']->format('Y-m-d H:i:s') : null,
            'delivered_at' => isset($timeMap['delivered']) ? $timeMap['delivered']->format('Y-m-d H:i:s') : null,
            'completed_at' => isset($timeMap['completed']) ? $timeMap['completed']->format('Y-m-d H:i:s') : null,
            'cancelled_at' => isset($timeMap['cancelled']) ? $timeMap['cancelled']->format('Y-m-d H:i:s') : null,
        ];
    }

    private function buildPayments(string $orderNumber, string $paymentMethod, float $paidAmount, DateTimeImmutable $orderDate): void
    {
        $timestamp = $orderDate->setTime(12, 0)->format('Y-m-d H:i:s');
        $this->orderPayments[$orderNumber] = [[
            'order_number' => $orderNumber,
            'payment_method' => $paymentMethod,
            'amount' => round($paidAmount, 2),
            'paid_at' => $paidAmount > 0 ? $timestamp : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]];
    }

    private function buildLogs(string $orderNumber, array $steps, array $timeMap): array
    {
        $logs = [];
        $prev = null;
        foreach ($steps as $step) {
            $time = $timeMap[$step] ?? new DateTimeImmutable();
            $logs[] = [
                'order_number' => $orderNumber,
                'from_status' => $prev,
                'to_status' => $step,
                'notes' => 'Auto demo log',
                'changed_by' => 1,
                'changed_at' => $time->format('Y-m-d H:i:s'),
                'created_at' => $time->format('Y-m-d H:i:s'),
                'updated_at' => $time->format('Y-m-d H:i:s'),
            ];
            $prev = $step;
        }

        return $logs;
    }

    private function cleanupExistingOrders(): void
    {
        $existing = $this->db->table('orders')
            ->select('id, order_number')
            ->like('order_number', 'DH-DEMO-', 'after')
            ->get()
            ->getResultArray();

        // Clean legacy orders không có order_number để tránh dữ liệu lệch total
        $legacy = $this->db->table('orders')
            ->select('id, order_number')
            ->where('order_number', null)
            ->get()
            ->getResultArray();

        $allOrders = array_merge($existing, $legacy);

        if (empty($existing)) {
            // vẫn phải xóa dữ liệu rác nếu có legacy
            if (empty($legacy)) {
                return;
            }
        }

        $orderIds = array_map(static fn ($row) => (int) $row['id'], $allOrders);
        if ($this->db->tableExists('order_status_logs')) {
            $this->db->table('order_status_logs')->whereIn('order_id', $orderIds)->delete();
        }
        if ($this->db->tableExists('order_payments')) {
            $this->db->table('order_payments')->whereIn('order_id', $orderIds)->delete();
        }
        if ($this->db->tableExists('order_items')) {
            $this->db->table('order_items')->whereIn('order_id', $orderIds)->delete();
        }
        $this->db->table('orders')->whereIn('id', $orderIds)->delete();
    }

    private function loadPriceMap(): array
    {
        $map = [];
        $products = $this->db->table('products')->select('id, selling_price')->get()->getResultArray();
        foreach ($products as $row) {
            $map[(int) $row['id']]['price'] = (float) $row['selling_price'];
        }
        if ($this->db->tableExists('product_variants_v2')) {
            $variants = $this->db->table('product_variants_v2')->select('id, product_id, price')->get()->getResultArray();
            foreach ($variants as $row) {
                $productId = (int) $row['product_id'];
                $variantId = (int) $row['id'];
                $map[$productId]['variants'][$variantId] = (float) $row['price'];
            }
        }

        return $map;
    }

    private function loadCustomers(): array
    {
        if (! $this->db->tableExists('customers')) {
            return [];
        }
        $rows = $this->db->table('customers')
            ->select('id, name, phone, address, province, district, ward')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'name' => $row['name'] ?? 'Demo Customer',
                'phone' => $row['phone'] ?? '0912000999',
                'address' => $row['address'] ?? 'Kho tổng Lano',
                'city' => $row['province'] ?? 'Hà Nội',
                'district' => $row['district'] ?? 'Hoàn Kiếm',
                'ward' => $row['ward'] ?? 'Hàng Bài',
            ];
        }

        return $map;
    }

    private function shippingInfo(int $customerId): array
    {
        return $this->customerMap[$customerId] ?? [
            'name' => 'Kho Lano',
            'phone' => '0912000999',
            'address' => 'Kho tổng Lano',
            'city' => 'Hà Nội',
            'district' => 'Hoàn Kiếm',
            'ward' => 'Hàng Bài',
        ];
    }

    private function orderDefinitions(): array
    {
        return [
            [
                'order_number' => 'DH-DEMO-001',
                'customer_id' => 2001,
                'branch_id' => 1,
                'status' => 'draft',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 30000,
                'paid_ratio' => 0.0,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-002',
                'customer_id' => 2002,
                'branch_id' => 2,
                'status' => 'draft',
                'order_type' => 'online',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 40000,
                'paid_ratio' => 0.35,
                'items' => [
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 2, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-003',
                'customer_id' => 2003,
                'branch_id' => 3,
                'status' => 'processing',
                'order_type' => 'online',
                'payment_method' => 'COD',
                'shipping_fee' => 35000,
                'paid_ratio' => 0.4,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'vip20'],
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-004',
                'customer_id' => 2004,
                'branch_id' => 4,
                'status' => 'processing',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 0.5,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 2, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-005',
                'customer_id' => 2005,
                'branch_id' => 5,
                'status' => 'processing',
                'order_type' => 'online',
                'payment_method' => 'EWALLET',
                'shipping_fee' => 45000,
                'paid_ratio' => 0.2,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7002, 'quantity' => 1, 'pricing' => 'flash30'],
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-006',
                'customer_id' => 2006,
                'branch_id' => 1,
                'status' => 'shipping',
                'order_type' => 'online',
                'payment_method' => 'COD',
                'shipping_fee' => 25000,
                'paid_ratio' => 0.6,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-007',
                'customer_id' => 2007,
                'branch_id' => 2,
                'status' => 'shipping',
                'order_type' => 'online',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 20000,
                'paid_ratio' => 0.5,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-008',
                'customer_id' => 2008,
                'branch_id' => 3,
                'status' => 'shipping',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 0.7,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'vip20'],
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-009',
                'customer_id' => 2011,
                'branch_id' => 1,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 0,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-010',
                'customer_id' => 2012,
                'branch_id' => 2,
                'status' => 'completed',
                'order_type' => 'online',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 30000,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 2, 'pricing' => 'standard'],
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'vip20'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-011',
                'customer_id' => 2013,
                'branch_id' => 3,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 20000,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 501, 'variant_id' => 7002, 'quantity' => 1, 'pricing' => 'flash30'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-012',
                'customer_id' => 2014,
                'branch_id' => 4,
                'status' => 'completed',
                'order_type' => 'online',
                'payment_method' => 'COD',
                'shipping_fee' => 0,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-013',
                'customer_id' => 2015,
                'branch_id' => 5,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 15000,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'vip20'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-014',
                'customer_id' => 2016,
                'branch_id' => 1,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 2, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-015',
                'customer_id' => 2017,
                'branch_id' => 2,
                'status' => 'completed',
                'order_type' => 'online',
                'payment_method' => 'EWALLET',
                'shipping_fee' => 25000,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-016',
                'customer_id' => 2018,
                'branch_id' => 3,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-017',
                'customer_id' => 2019,
                'branch_id' => 4,
                'status' => 'completed',
                'order_type' => 'online',
                'payment_method' => 'BANK_TRANSFER',
                'shipping_fee' => 40000,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 2, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-018',
                'customer_id' => 2020,
                'branch_id' => 5,
                'status' => 'completed',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 1.0,
                'items' => [
                    ['product_id' => 501, 'variant_id' => 7001, 'quantity' => 1, 'pricing' => 'vip20'],
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-019',
                'customer_id' => 2009,
                'branch_id' => 6,
                'status' => 'cancelled',
                'order_type' => 'offline',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_ratio' => 0.0,
                'cancellation_reason' => 'Khách đổi ý',
                'items' => [
                    ['product_id' => 502, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
            [
                'order_number' => 'DH-DEMO-020',
                'customer_id' => 2010,
                'branch_id' => 2,
                'status' => 'cancelled',
                'order_type' => 'online',
                'payment_method' => 'COD',
                'shipping_fee' => 20000,
                'paid_ratio' => 0.15,
                'cancellation_reason' => 'Hết hàng',
                'items' => [
                    ['product_id' => 503, 'variant_id' => null, 'quantity' => 1, 'pricing' => 'standard'],
                ],
            ],
        ];
    }

    private function calculatePaidAmount(float $total, float $ratio): float
    {
        $ratio = max(0, min(1, $ratio));
        return round($total * $ratio, 2);
    }

    private function normalizeMethod(string $method): string
    {
        $upper = strtoupper($method);
        return $upper === 'E_WALLET' ? 'EWALLET' : $upper;
    }

    private function resolvePaymentStatus(float $paidAmount, float $total): string
    {
        if ($total <= 0) {
            return 'unpaid';
        }
        if ($paidAmount + 0.01 >= $total) {
            return 'paid';
        }
        if ($paidAmount <= 0) {
            return 'unpaid';
        }
        return 'partial';
    }

    private function vatRateForIndex(int $index): float
    {
        $rates = [0.0, 0.05, 0.1];
        return $rates[$index % count($rates)];
    }

    private function templateIdForRate(float $rate): ?int
    {
        $key = number_format($rate, 2, '.', '');
        return $this->vatTemplateMap[$key] ?? null;
    }

    private function calculateVat(float $finalTotal, float $shippingFee, float $rate): float
    {
        $net = max(0, $finalTotal + $shippingFee);
        return round($net * $rate, 2);
    }

    private function basePrice(int $productId, ?int $variantId): float
    {
        if ($variantId && isset($this->priceMap[$productId]['variants'][$variantId])) {
            return (float) $this->priceMap[$productId]['variants'][$variantId];
        }
        if (isset($this->priceMap[$productId]['price'])) {
            return (float) $this->priceMap[$productId]['price'];
        }
        return 0;
    }
}
