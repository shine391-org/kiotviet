<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * ReturnsDemoSeeder - Demo return orders for testing return workflows
 * 
 * @agent-seeder: Demo returns
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: OrdersDemoSeeder, ProductsDemoSeeder
 */
class ReturnsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('returns')) {
            return;
        }

        echo "   → Demo return orders...\n";

        $this->cleanupExisting();
        $this->cleanupOrphans();

        $orders = DemoOrderHelper::orders($this->db, ['completed']);
        ksort($orders);
        if (empty($orders)) {
            echo "      ⚠️  No completed orders found for returns\n";
            return;
        }

        $orderIds = array_map(static fn ($row) => (int) $row['id'], array_values($orders));
        $itemsByOrder = DemoOrderHelper::orderItemsByOrder($this->db, $orderIds);

        [$returns, $returnItems] = $this->buildReturns($orders, $itemsByOrder);

        $returnIdMap = [];
        foreach ($returns as $row) {
            $this->db->table('returns')->insert($row);
            $returnIdMap[$row['return_number']] = (int) $this->db->insertID();
        }

        if (! empty($returnItems) && $this->db->tableExists('return_items')) {
            $rows = [];
            foreach ($returnItems as $item) {
                $returnId = $returnIdMap[$item['return_number']] ?? null;
                if (! $returnId) {
                    continue;
                }
                $rows[] = [
                    'return_id' => $returnId,
                    'order_item_id' => $item['order_item_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'item_condition' => $item['item_condition'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }
            if (! empty($rows)) {
                $this->db->table('return_items')->insertBatch($rows);
            }
        }

        echo "      ✓ Created " . count($returns) . " demo returns\n";
        echo "      ✓ Created " . count($returnItems) . " return items\n";
    }

    private function buildReturns(array $orders, array $itemsByOrder): array
    {
        $plans = [
            [
                'return_number' => 'RET-DEMO-001',
                'order_number' => 'DH-DEMO-010',
                'status' => 'approved',
                'reason' => 'defective',
                'refund_method' => 'cash',
                'created_by' => 1,
                'item_product_id' => 503,
                'quantity' => 1,
                'condition' => 'damaged',
            ],
            [
                'return_number' => 'RET-DEMO-002',
                'order_number' => 'DH-DEMO-011',
                'status' => 'completed',
                'reason' => 'not_satisfied',
                'refund_method' => 'bank_transfer',
                'created_by' => 2,
                'item_product_id' => 501,
                'quantity' => 1,
                'condition' => 'used',
            ],
            [
                'return_number' => 'RET-DEMO-003',
                'order_number' => 'DH-DEMO-013',
                'status' => 'pending',
                'reason' => 'wrong_item',
                'refund_method' => null,
                'created_by' => 1,
                'item_product_id' => 502,
                'quantity' => 1,
                'condition' => 'new',
            ],
            [
                'return_number' => 'RET-DEMO-004',
                'order_number' => 'DH-DEMO-015',
                'status' => 'rejected',
                'reason' => 'other',
                'refund_method' => null,
                'created_by' => 2,
                'reason_detail' => 'Không phù hợp với nhu cầu',
                'item_product_id' => 503,
                'quantity' => 1,
                'condition' => 'new',
            ],
        ];

        $returns = [];
        $items = [];
        $now = Time::now()->toDateTimeString();

        foreach ($plans as $plan) {
            $order = $orders[$plan['order_number']] ?? null;
            if (! $order) {
                continue;
            }
            $orderItems = $itemsByOrder[(int) $order['id']] ?? [];
            $orderItem = $this->findOrderItem($orderItems, (int) $plan['item_product_id']);
            if (! $orderItem) {
                continue;
            }
            $qty = min((float) $plan['quantity'], (float) ($orderItem['quantity'] ?? 0));
            $lineTotal = round((float) ($orderItem['final_price'] ?? 0) * $qty, 2);
            $shouldRefund = in_array($plan['status'], ['approved', 'completed'], true);

            $returns[] = [
                'return_number' => $plan['return_number'],
                'order_id' => (int) $order['id'],
                'customer_id' => $order['customer_id'] ?? null,
                'return_amount' => $lineTotal,
                'refund_shipping_fee' => 0,
                'refund_amount' => $shouldRefund ? $lineTotal : 0,
                'refund_method' => $plan['refund_method'],
                'reason' => $plan['reason'],
                'reason_detail' => $plan['reason_detail'] ?? null,
                'status' => $plan['status'],
                'created_by' => $plan['created_by'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $items[] = [
                'return_number' => $plan['return_number'],
                'order_item_id' => (int) $orderItem['id'],
                'quantity_returned' => $qty,
                'item_condition' => $plan['condition'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return [$returns, $items];
    }

    private function findOrderItem(array $orderItems, int $productId): ?array
    {
        foreach ($orderItems as $item) {
            if ((int) $item['product_id'] === $productId) {
                return $item;
            }
        }
        return $orderItems[0] ?? null;
    }

    private function cleanupOrphans(): void
    {
        if (! $this->db->tableExists('returns') || ! $this->db->tableExists('orders')) {
            return;
        }

        $rows = $this->db->table('returns r')
            ->select('r.id')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->where('o.id IS NULL', null, false)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        $returnIds = array_map(static fn ($row) => (int) $row['id'], $rows);
        if ($this->db->tableExists('return_items')) {
            $this->db->table('return_items')->whereIn('return_id', $returnIds)->delete();
        }
        $this->db->table('returns')->whereIn('id', $returnIds)->delete();
    }

    private function cleanupExisting(): void
    {
        $returnIds = [];
        if ($this->db->tableExists('returns')) {
            $rows = $this->db->table('returns')
                ->select('id')
                ->like('return_number', 'RET-DEMO-', 'after')
                ->get()
                ->getResultArray();
            $returnIds = array_map(static fn ($row) => (int) $row['id'], $rows);
        }

        if (! empty($returnIds) && $this->db->tableExists('return_items')) {
            $this->db->table('return_items')->whereIn('return_id', $returnIds)->delete();
        }
        if (! empty($returnIds)) {
            $this->db->table('returns')->whereIn('id', $returnIds)->delete();
        }
    }
}
