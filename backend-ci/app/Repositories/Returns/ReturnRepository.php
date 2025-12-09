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

    public function db(): BaseConnection
    {
        return $this->db;
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
        $row = $this->db->table($this->returns->table . ' AS r')
            ->select('
                r.*,
                r.created_at AS return_time,
                o.order_number AS invoice_code,
                c.name AS customer_name,
                b.name AS branch_name,
                u.full_name AS seller_name
            ')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->join('customers c', 'c.id = r.customer_id', 'left')
            ->join('branches b', 'b.id = o.branch_id', 'left')
            ->join('users u', 'u.id = r.created_by', 'left')
            ->where('r.id', $id)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }
        $row = $this->hydrate($row);
        $row['items'] = $this->items->where('return_id', $id)->findAll();
        
        // Fetch payment history from cash_transactions
        $row['payment_history'] = $this->getPaymentHistory($id);
        
        return $row;
    }

    /** Get payment history for a return from cash_transactions */
    public function getPaymentHistory(int $returnId): array
    {
        $payments = $this->db->table('cash_transactions AS ct')
            ->select('
                ct.id,
                ct.reference_code AS receipt_code,
                ct.created_at,
                ct.amount,
                ct.payment_method,
                ct.status,
                ct.created_by_name AS creator_name,
                ct.staff_name AS receiver_name,
                ct.bank_account AS account_number,
                ct.note AS notes
            ')
            ->where('ct.reference_type', 'return')
            ->where('ct.reference_id', $returnId)
            ->where('ct.deleted_at IS NULL')
            ->orderBy('ct.created_at', 'DESC')
            ->get()
            ->getResultArray();
        
        return $payments;
    }

    /** List returns with filters + pagination. */
    public function findAll(array $filters): array
    {
        $b = $this->db->table($this->returns->table . ' AS r')
            ->select('
                r.id,
                r.return_number,
                r.order_id,
                r.customer_id,
                r.return_amount,
                r.refund_shipping_fee,
                r.refund_amount,
                r.refund_method,
                r.reason,
                r.reason_detail,
                r.status,
                r.approved_by,
                r.approved_at,
                r.rejected_by,
                r.rejected_at,
                r.completed_at,
                r.notes,
                r.lock_version,
                r.created_by,
                r.created_at,
                r.updated_at,
                r.created_at AS return_time,
                o.order_number AS invoice_code,
                c.name AS customer_name,
                b.name AS branch_name,
                u.full_name AS seller_name
            ')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->join('customers c', 'c.id = r.customer_id', 'left')
            ->join('branches b', 'b.id = o.branch_id', 'left')
            ->join('users u', 'u.id = r.created_by', 'left')
            ->where('r.deleted_at IS NULL', null, false);

        $this->applyFiltersToBuilder($b, $filters);

        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $rows = $b->orderBy('r.created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    public function count(array $filters): int
    {
        $b = $this->db->table($this->returns->table . ' AS r')
            ->where('r.deleted_at IS NULL', null, false);
        $this->applyFiltersToBuilder($b, $filters);
        return $b->countAllResults();
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

    private function applyFiltersToBuilder($b, array $filters): void
    {
        if (! empty($filters['order_id'])) {
            $b->where('r.order_id', $filters['order_id']);
        }
        if (! empty($filters['customer_id'])) {
            $b->where('r.customer_id', $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('r.status', $filters['status']);
        }
        if (! empty($filters['return_number'])) {
            $b->like('r.return_number', $filters['return_number']);
        }
        if (! empty($filters['search'])) {
            $b->like('r.return_number', $filters['search']);
        }
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
