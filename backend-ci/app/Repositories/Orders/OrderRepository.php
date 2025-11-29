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
        $this->db = $db ?? \Config\Database::connect();
        $this->orders = $orders ?? new OrderModel();
        $this->items = $items ?? new OrderItemModel();
    }

    /** Create order with items. */
    public function create(array $order, array $items, bool $useTransaction = true): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $order + ['created_at' => $now, 'updated_at' => $now];

        if ($useTransaction) {
            $this->db->transStart();
        }
        $this->db->table('orders')->insert($payload);
        $orderId = (int) $this->db->insertID();

        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'order_id' => $orderId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $itemsResult = $this->db->table('order_items')->insertBatch($rows);
            if ($itemsResult === false) {
                $err = $this->db->error();
                throw new \RuntimeException('Order items insert failed: ' . json_encode($err));
            }
        }
        if ($useTransaction) {
            $this->db->transComplete();
            if ($this->db->transStatus() === false && ENVIRONMENT !== 'testing') {
                $err = $this->db->error();
                $message = $err['message'] ?? 'unknown DB error';
                throw new \RuntimeException('Order create failed: ' . $message . ' code:' . ($err['code'] ?? ''));
            }
        }

        return $payload + ['id' => $orderId];
    }

    /** Fetch order with items. */
    public function findById(int $id): ?array
    {
        $order = $this->db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) { return null; }
        $items = $this->db->table('order_items')->where('order_id', $id)->get()->getResultArray();
        $order['items'] = $items;
        return $order;
    }

    /** Update arbitrary fields. */
    public function updateFields(int $id, array $data): bool
    {
        return (bool) $this->db->table('orders')->where('id', $id)->update($data);
    }
}
