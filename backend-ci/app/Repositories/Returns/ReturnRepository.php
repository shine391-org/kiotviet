<?php

namespace App\Repositories\Returns;

use App\Models\ReturnItemModel;
use App\Models\ReturnModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Returns persistence.
 *
 * @agent-repository: Returns
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ReturnRepository
{
    protected ReturnModel $returns;
    protected ReturnItemModel $items;
    protected BaseConnection $db;

    public function __construct(
        ?ReturnModel $returns = null,
        ?ReturnItemModel $items = null,
        ?BaseConnection $db = null
    ) {
        $this->returns = $returns ?? new ReturnModel();
        $this->items = $items ?? new ReturnItemModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Generate next return number per order (TX-safe). */
    public function nextNumber(int $orderId): string
    {
        $this->db->transStart();
        $sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->db->protectIdentifiers($this->returns->table) . ' WHERE order_id = ?';
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= ' FOR UPDATE';
        }
        $row = $this->db->query($sql, [$orderId])->getRowArray();
        $count = (int) ($row['cnt'] ?? 0);
        $number = sprintf('TH-%d-%d', $orderId, $count + 1);
        $this->db->transComplete();
        return $number;
    }

    /** Create return with items. */
    public function create(array $returnRow, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $returnRow + [
            'created_at' => $returnRow['created_at'] ?? $now,
            'updated_at' => $returnRow['updated_at'] ?? $now,
            'lock_version' => $returnRow['lock_version'] ?? 0,
        ];

        $this->db->transStart();
        $this->returns->insert($payload);
        $returnId = (int) $this->returns->getInsertID();

        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = [
                    'return_id' => $returnId,
                    'order_item_id' => $item['order_item_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'item_condition' => $item['item_condition'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('return_items')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $this->findById($returnId) ?? ($payload + ['id' => $returnId, 'items' => $items]);
    }

    /** Fetch return with items. */
    public function findById(int $id): ?array
    {
        $row = $this->returns->find($id);
        if (! $row) {
            return null;
        }
        $row = $this->hydrate($row);
        $row['items'] = $this->items->where('return_id', $id)->findAll();
        return $row;
    }

    /** List returns with filters + pagination. */
    public function findAll(array $filters): array
    {
        $b = $this->applyFilters($filters);
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $rows = $b->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /** Orders + order_items helper. */
    public function orderWithItems(int $orderId): ?array
    {
        $order = $this->db->table('orders')
            ->select('id, customer_id, branch_id, status, total, shipping_fee, completed_at, updated_at, created_at')
            ->where('id', $orderId)
            ->get()
            ->getRowArray();
        if (! $order) {
            return null;
        }
        $items = $this->db->table('order_items')
            ->select('id, order_id, product_id, variant_id, quantity, final_price, base_price')
            ->where('order_id', $orderId)
            ->get()->getResultArray();
        $order['items'] = $items;
        return $order;
    }

    /** Sum already approved/completed quantities for an order_item. */
    public function alreadyReturnedQty(int $orderItemId): float
    {
        $row = $this->db->table('return_items ri')
            ->select('SUM(ri.quantity_returned) as qty')
            ->join('returns r', 'r.id = ri.return_id')
            ->where('ri.order_item_id', $orderItemId)
            ->whereIn('r.status', ['approved', 'completed'])
            ->get()->getRowArray();
        return (float) ($row['qty'] ?? 0);
    }

    /** Transition status with optimistic lock. */
    public function transition(int $id, string $toStatus, array $extra = [], ?int $expectedVersion = null): array
    {
        $this->db->transStart();
        $builder = $this->db->table($this->returns->table)->where('id', $id);
        if ($expectedVersion !== null) {
            $builder->where('lock_version', $expectedVersion);
        }
        $payload = $extra + [
            'status' => $toStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $builder->set($payload);
        $builder->set('lock_version', 'lock_version + 1', false);
        $builder->update();
        $this->db->transComplete();

        $updated = $this->findById($id);
        if (! $updated) {
            throw new \RuntimeException('Return not found after update');
        }
        if ($expectedVersion !== null && $updated['lock_version'] <= $expectedVersion) {
            throw new \RuntimeException('Return was updated by another user');
        }
        return $updated;
    }

    private function applyFilters(array $filters)
    {
        $b = $this->returns->builder();
        if (! empty($filters['order_id'])) {
            $b->where('order_id', $filters['order_id']);
        }
        if (! empty($filters['customer_id'])) {
            $b->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['return_number'])) {
            $b->like('return_number', $filters['return_number']);
        }
        return $b;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['order_id'] = (int) ($row['order_id'] ?? 0);
        $row['customer_id'] = (int) ($row['customer_id'] ?? 0);
        $row['return_amount'] = isset($row['return_amount']) ? (float) $row['return_amount'] : 0.0;
        $row['refund_shipping_fee'] = (bool) ($row['refund_shipping_fee'] ?? false);
        $row['refund_amount'] = isset($row['refund_amount']) ? (float) $row['refund_amount'] : null;
        $row['lock_version'] = isset($row['lock_version']) ? (int) $row['lock_version'] : 0;
        return $row;
    }
}
