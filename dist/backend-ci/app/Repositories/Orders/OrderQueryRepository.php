<?php

namespace App\Repositories\Orders;

use CodeIgniter\Database\BaseConnection;

/**
 * Query-only repository for listing and reading orders.
 *
 * @agent-repository: Orders list queries
 * @agent-pattern: Read model repository
 * @agent-reusable: MEDIUM
 */
class OrderQueryRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Fetch paginated orders with optional filters.
     *
     * @agent-use: Orders listing with filters
     * @agent-pattern: Filtered query + pagination
     */
    public function list(array $filters): array
    {
        $builder = $this->baseSelect();

        if (! empty($filters['search'])) {
            $this->applySearch($builder, $filters['search']);
        }
        if (! empty($filters['branch_id'])) {
            $builder->where('o.branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['status'])) {
            $builder->whereIn('o.status', $filters['status']);
        }
        if (! empty($filters['payment_method'])) {
            $builder->where('o.payment_method', $filters['payment_method']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('o.order_date >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('o.order_date <=', $filters['date_to']);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $page = $filters['page'];
        $limit = $filters['limit'];
        $offset = ($page - 1) * $limit;

        $rows = $builder
            ->orderBy('o.order_date', 'DESC')
            ->orderBy('o.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        $totals = $this->aggregateTotals($filters);

        return [
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
            'totals' => $totals,
        ];
    }

    /**
     * Find order with its items.
     *
     * @agent-use: Order detail
     * @agent-pattern: Master-detail fetch
     */
    public function findWithItems(int $orderId): ?array
    {
        $orderQuery = $this->baseSelect()
            ->where('o.id', $orderId)
            ->get();
        $order = $orderQuery ? $orderQuery->getRowArray() : null;
        if (! $order) {
            return null;
        }
        $itemsQuery = $this->db->table('order_items')
            ->select('id, product_id, variant_id, quantity, base_price, final_price, price_list_name')
            ->where('order_id', $orderId)
            ->get();
        $items = $itemsQuery ? $itemsQuery->getResultArray() : [];
        $order['items'] = $items;
        return $order;
    }

    private function baseSelect()
    {
        return $this->db->table('orders o')
            ->select('o.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name')
            ->join('customers c', 'c.id = o.customer_id', 'left')
            ->join('branches b', 'b.id = o.branch_id', 'left')
            ->where('o.deleted_at', null);
    }

    private function applySearch($builder, string $term): void
    {
        $like = '%' . $this->db->escapeLikeString($term) . '%';
        $builder->groupStart()
            ->like('o.order_number', $term)
            ->orLike('o.shipping_phone', $term)
            ->orLike('o.shipping_name', $term)
            ->orLike('c.name', $term)
            ->groupEnd();
    }

    private function aggregateTotals(array $filters): array
    {
        $builder = $this->db->table('orders o')
            ->select('SUM(o.total) AS total_amount, SUM(o.paid_amount) AS paid_amount, SUM(o.debt_amount) AS debt_amount')
            ->join('customers c', 'c.id = o.customer_id', 'left')
            ->join('branches b', 'b.id = o.branch_id', 'left')
            ->where('o.deleted_at', null);

        if (! empty($filters['search'])) {
            $this->applySearch($builder, $filters['search']);
        }
        if (! empty($filters['branch_id'])) {
            $builder->where('o.branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['status'])) {
            $builder->whereIn('o.status', $filters['status']);
        }
        if (! empty($filters['payment_method'])) {
            $builder->where('o.payment_method', $filters['payment_method']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('o.order_date >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('o.order_date <=', $filters['date_to']);
        }

        $rowQuery = $builder->get();
        $row = $rowQuery ? $rowQuery->getRowArray() : null;
        return [
            'total_amount' => (float) ($row['total_amount'] ?? 0),
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'debt_amount' => (float) ($row['debt_amount'] ?? 0),
        ];
    }
}
