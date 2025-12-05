<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DashboardRepository;
use CodeIgniter\I18n\Time;

/**
 * Dashboard Service - Business logic for dashboard data
 * 
 * @agent-service: Dashboard analytics and metrics
 * @agent-pattern: Clean architecture service pattern
 * @agent-reusable: HIGH
 */
class DashboardService
{
    protected DashboardRepository $repo;

    public function __construct()
    {
        $this->repo = new DashboardRepository();
    }

    /**
     * Get today's KPI metrics
     * @agent-pattern: Standard KPI aggregation - COPY THIS
     */
    public function getKpiToday(?int $branchId = null): array
    {
        // 1. Get today's data
        $revenue = $this->repo->getTodayRevenue($branchId);
        $returns = $this->repo->getTodayReturns($branchId);
        $netRevenue = $revenue - $returns;
        
        // 2. Get comparison period (same day last month)
        $today = date('Y-m-d');
        $lastMonthDate = date('Y-m-d', strtotime('-1 month'));
        
        $previousRevenue = $this->repo->getRevenueForPeriod(
            $lastMonthDate,
            $lastMonthDate,
            $branchId
        );
        
        // 3. Calculate percentage change
        $netChange = 0;
        if ($previousRevenue > 0) {
            $netChange = (($netRevenue - $previousRevenue) / $previousRevenue) * 100;
        } elseif ($netRevenue > 0) {
            $netChange = 100; // Full growth if previous was 0
        }
        
        // 4. Format response
        return [
            'revenue' => $revenue,
            'returns' => $returns,
            'netRevenue' => $netRevenue,
            'netChange' => round($netChange, 2),
            'comparisonLabel' => 'so với cùng kỳ tháng trước',
        ];
    }

    /**
     * Get revenue chart data with filters
     * @agent-pattern: Chart data formatting - COPY THIS
     */
    public function getRevenueChart(array $filters = []): array
    {
        // 1. Extract and validate filters
        $period = $filters['period'] ?? 'day';
        $range = $filters['range'] ?? 'month';
        $branchId = $filters['branch_id'] ?? null;
        
        // Validate period
        if (!in_array($period, ['day', 'hour', 'weekday'])) {
            $period = 'day';
        }
        
        // Validate range
        if (!in_array($range, ['today', 'week', 'month', 'custom'])) {
            $range = 'month';
        }
        
        // 2. Get data from repository
        $data = $this->repo->getRevenueByPeriod($period, $range, $branchId);
        
        // 3. Format for chart
        $labels = [];
        $values = [];
        $total = 0;
        
        foreach ($data as $row) {
            $labels[] = $this->formatChartLabel($row['label'], $period);
            $value = (float)($row['net_revenue'] ?? 0);
            $values[] = $value;
            $total += $value;
        }
        
        // 4. Fill gaps if needed (for consistent chart display)
        [$labels, $values] = $this->fillChartGaps($labels, $values, $period, $range);
        
        return [
            'labels' => $labels,
            'values' => $values,
            'total' => $total,
            'branchLabel' => $branchId ? "Chi nhánh #{$branchId}" : 'Tất cả chi nhánh',
        ];
    }

    /**
     * Get top products ranking
     * @agent-pattern: Ranking list formatting
     */
    public function getTopProducts(array $filters = []): array
    {
        $metric = $filters['metric'] ?? 'net_revenue';
        $range = $filters['range'] ?? 'month';
        $limit = (int)($filters['limit'] ?? 10);
        
        // Validate metric
        $validMetrics = ['net_revenue', 'revenue', 'quantity', 'profit_margin'];
        if (!in_array($metric, $validMetrics)) {
            $metric = 'net_revenue';
        }
        
        // Validate limit
        if ($limit < 1 || $limit > 50) {
            $limit = 10;
        }
        
        $items = $this->repo->getTopProducts($metric, $range, $limit);
        
        return [
            'items' => $items,
            'metric' => $metric,
            'range' => $range,
        ];
    }

    /**
     * Get top customers ranking
     * @agent-pattern: Customer ranking formatting
     */
    public function getTopCustomers(array $filters = []): array
    {
        $range = $filters['range'] ?? 'month';
        $limit = (int)($filters['limit'] ?? 10);
        
        // Validate limit
        if ($limit < 1 || $limit > 50) {
            $limit = 10;
        }
        
        $items = $this->repo->getTopCustomers($range, $limit);
        
        return [
            'items' => $items,
            'range' => $range,
        ];
    }

    /**
     * Get recent activities timeline
     * @agent-pattern: Activity feed formatting
     */
    public function getActivities(array $filters = []): array
    {
        $limit = (int)($filters['limit'] ?? 15);
        
        // Validate limit
        if ($limit < 1 || $limit > 50) {
            $limit = 15;
        }
        
        $activities = $this->repo->getRecentActivities($limit);
        
        // Format timestamps to relative time
        $now = Time::now();
        foreach ($activities as &$activity) {
            if (isset($activity['timestamp'])) {
                $timestamp = Time::parse($activity['timestamp']);
                $activity['timeAgo'] = $this->getRelativeTime($timestamp, $now);
            }
        }
        
        return [
            'items' => $activities,
        ];
    }

    /**
     * Format chart label based on period type
     * @agent-pattern: Label formatting helper
     */
    private function formatChartLabel($label, string $period): string
    {
        return match($period) {
            'hour' => sprintf('%02d:00', $label),
            'weekday' => $this->getDayName((int)$label),
            default => date('d/m', strtotime($label)),
        };
    }

    /**
     * Get Vietnamese day name from day number (1=Sunday, 7=Saturday)
     */
    private function getDayName(int $dayNumber): string
    {
        $days = [
            1 => 'CN',  // Sunday
            2 => 'T2',  // Monday
            3 => 'T3',
            4 => 'T4',
            5 => 'T5',
            6 => 'T6',
            7 => 'T7',  // Saturday
        ];
        return $days[$dayNumber] ?? 'N/A';
    }

    /**
     * Fill gaps in chart data for consistent display
     * @agent-pattern: Data normalization helper
     */
    private function fillChartGaps(array $labels, array $values, string $period, string $range): array
    {
        // For now, return as-is. Can be enhanced to fill missing periods with 0 values
        // This would require generating expected labels based on period and range
        return [$labels, $values];
    }

    /**
     * Get relative time string (Vietnamese)
     * @agent-pattern: Time formatting helper
     */
    private function getRelativeTime(Time $timestamp, Time $now): string
    {
        $diff = $now->difference($timestamp);
        
        if ($diff->getYears() > 0) {
            return $diff->getYears() . ' năm trước';
        }
        if ($diff->getMonths() > 0) {
            return $diff->getMonths() . ' tháng trước';
        }
        if ($diff->getDays() > 0) {
            return $diff->getDays() . ' ngày trước';
        }
        if ($diff->getHours() > 0) {
            return $diff->getHours() . ' giờ trước';
        }
        if ($diff->getMinutes() > 0) {
            return $diff->getMinutes() . ' phút trước';
        }
        return 'Vừa xong';
    }
}
