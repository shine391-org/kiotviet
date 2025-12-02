<?php

namespace App\Repositories\Dashboard;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

/**
 * Dashboard data queries (orders, customers, cash transactions).
 *
 * @agent-repository: Dashboard data layer
 * @agent-pattern: Aggregation-focused repository
 */
class DashboardRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    /**
     * @agent-use: KPI aggregations (orders)
     * @agent-pattern: Reuseable range total
     */
    public function sumOrdersBetween(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?int $branchId = null,
        array $includeStatuses = [],
        array $excludeStatuses = []
    ): float {
        $builder = $this->db->table('orders')
            ->select('COALESCE(SUM(total), 0) AS value')
            ->where('order_date >=', $start->format('Y-m-d'))
            ->where('order_date <=', $end->format('Y-m-d'));

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }

        if (! empty($includeStatuses)) {
            $builder->whereIn('status', $includeStatuses);
        }

        if (! empty($excludeStatuses)) {
            $builder->whereNotIn('status', $excludeStatuses);
        }

        $result = $builder->get()->getRowArray();
        return (float) ($result['value'] ?? 0.0);
    }

    /**
     * @agent-use: Revenue chart data
     * @agent-pattern: Order timeline loader
     */
    public function fetchOrdersForRange(DateTimeImmutable $start, DateTimeImmutable $end, ?int $branchId = null): array
    {
        $builder = $this->db->table('orders')
            ->select('id,total,order_date,created_at,branch_id,status')
            ->where('order_date >=', $start->format('Y-m-d'))
            ->where('order_date <=', $end->format('Y-m-d'));
        $builder->whereNotIn('status', ['draft', 'cancelled']);

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->orderBy('order_date', 'ASC')->get()->getResultArray();
    }

    /**
     * @agent-use: Top products ranking
     * @agent-pattern: Join + aggregation
     */
    public function fetchProductMetrics(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $limit,
        ?int $branchId = null
    ): array {
        $builder = $this->db->table('order_items oi')
            ->select([
                'oi.product_id',
                'COALESCE(p.name, \'Sản phẩm không rõ\') AS name',
                'COALESCE(SUM(oi.quantity), 0) AS quantity',
                'COALESCE(SUM(oi.final_price * oi.quantity), 0) AS revenue',
                'COALESCE(SUM((oi.final_price - oi.base_price) * oi.quantity), 0) AS profit',
            ])
            ->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->where('o.order_date >=', $start->format('Y-m-d'))
            ->where('o.order_date <=', $end->format('Y-m-d'))
            ->whereNotIn('o.status', ['draft', 'cancelled']);

        if ($branchId !== null) {
            $builder->where('o.branch_id', $branchId);
        }

        return $builder->groupBy('oi.product_id')
            ->orderBy('revenue', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * @agent-use: Top customers ranking
     * @agent-pattern: Revenue per customer
     */
    public function fetchCustomerMetrics(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $limit,
        ?int $branchId = null
    ): array {
        $builder = $this->db->table('orders o')
            ->select([
                'o.customer_id',
                'COALESCE(c.full_name, c.name, \'Khách lẻ\') AS name',
                'COALESCE(SUM(o.total), 0) AS revenue',
            ])
            ->join('customers c', 'c.id = o.customer_id', 'left')
            ->where('o.order_date >=', $start->format('Y-m-d'))
            ->where('o.order_date <=', $end->format('Y-m-d'))
            ->whereNotIn('o.status', ['draft', 'cancelled']);

        if ($branchId !== null) {
            $builder->where('o.branch_id', $branchId);
        }

        return $builder->groupBy('o.customer_id')
            ->orderBy('revenue', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * @agent-use: Activity feed
     * @agent-pattern: Recent cash transactions
     */
    public function fetchRecentActivities(int $limit): array
    {
        return $this->db->table('cash_transactions ct')
            ->select([
                'ct.id',
                'ct.category',
                'ct.amount',
                'ct.created_at',
                'ct.transaction_date',
                'ct.reference_code',
                'ct.reference_type',
                'ct.type',
                'ct.branch_id',
                'ct.created_by_name',
                'u.username',
                'ct.created_by',
            ])
            ->join('users u', 'u.id = ct.created_by', 'left')
            ->orderBy('ct.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
