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

        // Attach related data (delivery notes, invoices) separately to avoid Cartesian duplicates
        $rows = $this->attachRelatedData($rows);

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

        // Attach related data for single order
        $orders = $this->attachRelatedData([$order]);
        $order = $orders[0] ?? $order;

        $itemsQuery = $this->db->table('order_items')
            ->select('id, product_id, variant_id, quantity, base_price, final_price, price_list_name')
            ->where('order_id', $orderId)
            ->get();
        $items = $itemsQuery ? $itemsQuery->getResultArray() : [];
        $order['items'] = $items;
        return $order;
    }

    /**
     * Base select - only orders with customer/branch joins.
     * Removed delivery_notes/invoices joins to prevent Cartesian duplicates.
     * Removed DATEDIFF(NOW()) to avoid non-deterministic queries.
     */
    private function baseSelect()
    {
        return $this->db->table('orders o')
            ->select('o.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name')
            ->join('customers c', 'c.id = o.customer_id', 'left')
            ->join('branches b', 'b.id = o.branch_id', 'left')
            ->where('o.deleted_at', null);
    }

    /**
     * Attach related delivery_notes and invoices data to order rows.
     * Fetches latest record per order to avoid duplicates.
     * Also computes waiting_days in PHP (instead of SQL DATEDIFF(NOW())).
     *
     * @param array $rows Order rows from main query
     * @return array Orders with tracking_code, delivery_date, invoice_code, waiting_days attached
     */
    private function attachRelatedData(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $orderIds = array_column($rows, 'id');
        $now = new \DateTime();

        // Fetch latest delivery note per order
        $deliveryNotes = $this->getLatestDeliveryNotesByOrderIds($orderIds);

        // Fetch latest invoice per order
        $invoices = $this->getLatestInvoicesByOrderIds($orderIds);

        // Attach to rows
        foreach ($rows as &$row) {
            $orderId = $row['id'];
            $row['tracking_code'] = $deliveryNotes[$orderId]['tracking_number'] ?? null;
            $row['delivery_date'] = $deliveryNotes[$orderId]['delivery_date'] ?? null;
            $row['invoice_code'] = $invoices[$orderId]['invoice_number'] ?? null;

            // Compute waiting_days in PHP instead of SQL DATEDIFF(NOW())
            $createdAt = $row['created_at'] ?? null;
            if ($createdAt) {
                try {
                    $created = new \DateTime($createdAt);
                    $row['waiting_days'] = $now->diff($created)->days;
                } catch (\Exception $e) {
                    $row['waiting_days'] = null;
                }
            } else {
                $row['waiting_days'] = null;
            }
        }

        return $rows;
    }

    /**
     * Get latest delivery note for each order ID.
     *
     * @param array $orderIds
     * @return array Indexed by order_id
     */
    private function getLatestDeliveryNotesByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        // Subquery to get max id per order_id (latest delivery note)
        $query = $this->db->table('delivery_notes dn1')
            ->select('dn1.order_id, dn1.tracking_number, dn1.delivery_date')
            ->whereIn('dn1.order_id', $orderIds)
            ->where('dn1.id = (SELECT MAX(dn2.id) FROM delivery_notes dn2 WHERE dn2.order_id = dn1.order_id)', null, false)
            ->get();

        $results = [];
        if ($query) {
            foreach ($query->getResultArray() as $row) {
                $results[$row['order_id']] = $row;
            }
        }
        return $results;
    }

    /**
     * Get latest invoice for each order ID.
     *
     * @param array $orderIds
     * @return array Indexed by order_id
     */
    private function getLatestInvoicesByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        // Join invoice_orders with invoices, get latest invoice per order
        $query = $this->db->table('invoice_orders io')
            ->select('io.order_id, i.invoice_number')
            ->join('invoices i', 'i.id = io.invoice_id', 'inner')
            ->whereIn('io.order_id', $orderIds)
            ->where('io.invoice_id = (SELECT MAX(io2.invoice_id) FROM invoice_orders io2 WHERE io2.order_id = io.order_id)', null, false)
            ->get();

        $results = [];
        if ($query) {
            foreach ($query->getResultArray() as $row) {
                $results[$row['order_id']] = $row;
            }
        }
        return $results;
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

