<?php

namespace App\Repositories\Orders;

use App\Models\OrderItemModel;
use App\Models\OrderModel;
use CodeIgniter\Database\BaseConnection;

/** Order persistence. @agent-repository: Orders @agent-pattern: Repository pattern @agent-reusable: MEDIUM */
class OrderRepository
{
    protected OrderModel $orders;
    protected OrderItemModel $items;
    protected BaseConnection $db;

    public function __construct(?OrderModel $orders = null, ?OrderItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->orders = $orders ?? new OrderModel();
        $this->items = $items ?? new OrderItemModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Create order with items. */
    public function create(array $order, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $order + ['created_at' => $now, 'updated_at' => $now];

        $this->db->transStart();
        $this->orders->insert($payload);
        $orderId = (int) $this->orders->getInsertID();

        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'order_id' => $orderId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('order_items')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $payload + ['id' => $orderId];
    }

    /** Fetch order with items. */
    public function findById(int $id): ?array
    {
        $order = $this->orders->find($id);
        if (! $order) { return null; }
        $items = $this->items->where('order_id', $id)->findAll();
        $order['items'] = $items;
        return $order;
    }
}
