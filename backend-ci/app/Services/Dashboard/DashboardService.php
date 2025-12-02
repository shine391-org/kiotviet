<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DashboardRepository;
use App\Validators\DashboardValidator;
use DateTimeImmutable;

/**
 * Dashboard business logic - KPI, charts, rankings, activities.
 *
 * @agent-service: Dashboard analytics
 * @agent-pattern: Read-only aggregation
 * @agent-reusable: HIGH
 */
class DashboardService
{
    protected DashboardRepository $repository;
    protected DashboardValidator $validator;

    public function __construct(DashboardRepository $repository, DashboardValidator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * @agent-use: GET /api/dashboard/kpi-today
     * @agent-pattern: KPI aggregator (month vs previous)
     */
    public function kpiToday(array $filters = []): array
    {
        $data = $this->validator->validateKpi($filters);
        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;

        $today = new DateTimeImmutable('today');
        $start = new DateTimeImmutable('first day of this month');
        $end = $today;
        $previousStart = $start->modify('-1 month');
        $previousEnd = $start->modify('-1 day');

        $revenue = $this->repository->sumOrdersBetween($start, $end, $branchId, [], ['draft', 'cancelled']);
        $returns = $this->repository->sumOrdersBetween($start, $end, $branchId, ['cancelled'], []);
        $netRevenue = $revenue - $returns;

        $previousNet = $this->repository->sumOrdersBetween($previousStart, $previousEnd, $branchId, [], ['draft', 'cancelled']);
        $netChange = $this->calculatePercentageChange($netRevenue, $previousNet);

        return [
            'success' => true,
            'data' => [
                'revenue' => (float) $revenue,
                'returns' => (float) $returns,
                'netRevenue' => (float) $netRevenue,
                'netChange' => $netChange,
                'comparisonLabel' => 'so với cùng kỳ tháng trước',
            ],
        ];
    }

    /**
     * @agent-use: GET /api/dashboard/revenue-chart
     * @agent-pattern: Timeline generation
     */
    public function revenueChart(array $filters = []): array
    {
        $data = $this->validator->validateChartFilters($filters);
        $period = $data['period'] ?? 'day';
        $range = $data['range'] ?? 'month';
        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;
        [$start, $end] = $this->resolveRange($range, $data['from_date'] ?? null, $data['to_date'] ?? null);

        $orders = $this->repository->fetchOrdersForRange($start, $end, $branchId);
        $chart = $this->buildChartData($orders, $period, $start, $end);
        $chart['branchLabel'] = $branchId ? "Chi nhánh {$branchId}" : 'Tất cả chi nhánh';

        return [
            'success' => true,
            'chart' => $chart,
        ];
    }

    /**
     * @agent-use: GET /api/dashboard/top-products
     * @agent-pattern: Aggregated ranking
     */
    public function topProducts(array $filters = []): array
    {
        $data = $this->validator->validateTopProductsFilters($filters);
        $range = $data['range'] ?? 'month';
        $limit = isset($data['limit']) ? (int) $data['limit'] : 10;
        $metric = $data['metric'] ?? 'net_revenue';
        $branchId = null;
        $fromDate = $data['from_date'] ?? null;
        $toDate = $data['to_date'] ?? null;
        [$start, $end] = $this->resolveRange($range, $fromDate, $toDate);

        $rows = $this->repository->fetchProductMetrics($start, $end, $limit, $branchId);
        $items = array_map(fn ($row) => [
            'id' => $row['product_id'],
            'name' => $row['name'],
            'value' => $this->resolveMetricValue($row, $metric),
        ], $rows);

        return [
            'success' => true,
            'items' => $items,
        ];
    }

    /**
     * @agent-use: GET /api/dashboard/top-customers
     * @agent-pattern: Aggregated ranking
     */
    public function topCustomers(array $filters = []): array
    {
        $data = $this->validator->validateTopCustomersFilters($filters);
        $range = $data['range'] ?? 'month';
        $limit = isset($data['limit']) ? (int) $data['limit'] : 10;
        $branchId = null;
        [$start, $end] = $this->resolveRange($range, $data['from_date'] ?? null, $data['to_date'] ?? null);

        $rows = $this->repository->fetchCustomerMetrics($start, $end, $limit, $branchId);
        $items = array_map(fn ($row) => [
            'id' => $row['customer_id'] ?? null,
            'name' => $row['name'],
            'value' => (float) $row['revenue'],
        ], $rows);

        return [
            'success' => true,
            'items' => $items,
        ];
    }

    /**
     * @agent-use: GET /api/dashboard/activities
     * @agent-pattern: Recent activity reader
     */
    public function activities(array $filters = []): array
    {
        $data = $this->validator->validateActivitiesFilters($filters);
        $limit = isset($data['limit']) ? (int) $data['limit'] : 15;

        $rows = $this->repository->fetchRecentActivities($limit);
        $items = array_map(fn ($row) => $this->mapActivityRow($row), $rows);

        return [
            'success' => true,
            'items' => $items,
        ];
    }

    private function resolveRange(string $range, ?string $fromDate, ?string $toDate): array
    {
        $today = new DateTimeImmutable('today');
        switch ($range) {
            case 'today':
                $start = $today;
                $end = $today;
                break;
            case 'week':
                $start = new DateTimeImmutable('monday this week');
                $end = new DateTimeImmutable('sunday this week');
                break;
            case 'month':
                $start = new DateTimeImmutable('first day of this month');
                $end = new DateTimeImmutable('last day of this month');
                break;
            case 'custom':
                $start = $this->parseDateOrDefault($fromDate, new DateTimeImmutable('first day of this month'));
                $end = $this->parseDateOrDefault($toDate, $today);
                break;
            default:
                $start = new DateTimeImmutable('first day of this month');
                $end = $today;
        }

        if ($end < $start) {
            $end = $start;
        }

        return [$start, $end];
    }

    private function parseDateOrDefault(?string $value, DateTimeImmutable $fallback): DateTimeImmutable
    {
        if (empty($value)) {
            return $fallback;
        }
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $parsed ?: $fallback;
    }

    private function buildChartData(array $orders, string $period, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $buckets = $this->initializeBuckets($period, $start, $end);

        foreach ($orders as $order) {
            $label = $this->labelForPeriod($order, $period);
            if (! isset($buckets[$label])) {
                $buckets[$label] = 0;
            }
            $buckets[$label] += (float) ($order['total'] ?? 0);
        }

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
            'total' => array_sum(array_values($buckets)),
        ];
    }

    private function initializeBuckets(string $period, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $buckets = [];
        if ($period === 'hour') {
            $cursor = $start->setTime(0, 0);
            $endHour = $end->setTime(23, 0);
            while ($cursor <= $endHour) {
                $buckets[$cursor->format('H:00')] = 0;
                $cursor = $cursor->modify('+1 hour');
            }
        } elseif ($period === 'weekday') {
            foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $label) {
                $buckets[$label] = 0;
            }
        } else {
            $cursor = $start;
            while ($cursor <= $end) {
                $buckets[$cursor->format('Y-m-d')] = 0;
                $cursor = $cursor->modify('+1 day');
            }
        }
        return $buckets;
    }

    private function labelForPeriod(array $order, string $period): string
    {
        $source = $order['created_at'] ?? $order['order_date'] ?? date('Y-m-d');
        try {
            $time = new DateTimeImmutable($source);
        } catch (\Throwable $e) {
            $time = new DateTimeImmutable();
        }

        return match ($period) {
            'hour' => $time->format('H:00'),
            'weekday' => $time->format('D'),
            default => $time->format('Y-m-d'),
        };
    }

    private function resolveMetricValue(array $row, string $metric): float
    {
        return match ($metric) {
            'quantity' => (float) ($row['quantity'] ?? 0),
            'profit_margin' => (float) ($row['profit'] ?? 0),
            default => (float) ($row['revenue'] ?? 0),
        };
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ((float) $previous === 0.0) {
            return 0.0;
        }
        return (($current - $previous) / abs($previous)) * 100.0;
    }

    private function mapActivityRow(array $row): array
    {
        $typeMap = [
            'sales' => ['type' => 'invoice', 'action' => 'Bán đơn hàng'],
            'shipping_cod' => ['type' => 'invoice', 'action' => 'Đối soát COD'],
            'return' => ['type' => 'return', 'action' => 'Nhận trả hàng'],
            'refund' => ['type' => 'return', 'action' => 'Hoàn tiền'],
            'deposit' => ['type' => 'purchase', 'action' => 'Nộp quỹ'],
            'purchase' => ['type' => 'purchase', 'action' => 'Nhập hàng'],
            'other_income' => ['type' => 'invoice', 'action' => 'Thu khác'],
            'shipping_fee' => ['type' => 'delivery', 'action' => 'Kho hàng'],
        ];

        $category = $row['category'] ?? 'sales';
        $meta = $typeMap[$category] ?? ['type' => 'invoice', 'action' => 'Giao dịch'];

        return [
            'id' => 'activity-' . ($row['id'] ?? uniqid()),
            'type' => $meta['type'],
            'username' => $row['created_by_name'] ?? $row['username'] ?? 'Hệ thống',
            'action' => $meta['action'],
            'amount' => (float) ($row['amount'] ?? 0),
            'timestamp' => $row['created_at'] ?? $row['transaction_date'] ?? date('Y-m-d H:i:s'),
            'linkedPage' => '#',
        ];
    }
}
