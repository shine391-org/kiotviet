<?php

namespace App\Repositories\Dashboard;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;

/**
 * Dashboard Repository - Database operations for dashboard metrics
 * 
 * @agent-repository: Dashboard data aggregation
 * @agent-pattern: Clean architecture repository pattern
 * @agent-reusable: HIGH
 */
class DashboardRepository
{
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get today's total revenue
     * @agent-pattern: Standard aggregation query
     */
    public function getTodayRevenue(?int $branchId = null): float
    {
        $today = date('Y-m-d');
        
        $builder = $this->db->table('invoices')
            ->selectSum('total', 'revenue')
            ->where('DATE(issue_date)', $today);
        
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        
        $result = $builder->get()->getRowArray();
        return (float)($result['revenue'] ?? 0);
    }

    /**
     * Get today's total returns
     * @agent-pattern: Standard aggregation query
     */
    public function getTodayReturns(?int $branchId = null): float
    {
        $today = date('Y-m-d');
        
        $builder = $this->db->table('returns')
            ->selectSum('return_amount', 'returns')
            ->where('DATE(updated_at)', $today);
        
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        
        $result = $builder->get()->getRowArray();
        return (float)($result['returns'] ?? 0);
    }

    /**
     * Get revenue for a specific date range
     * @agent-pattern: Date range aggregation
     */
    public function getRevenueForPeriod(string $startDate, string $endDate, ?int $branchId = null): float
    {
        $builder = $this->db->table('invoices')
            ->selectSum('total', 'revenue')
            ->where('DATE(issue_date) >=', $startDate)
            ->where('DATE(issue_date) <=', $endDate);
        
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        
        $result = $builder->get()->getRowArray();
        return (float)($result['revenue'] ?? 0);
    }

    /**
     * Get revenue grouped by period (day/hour/weekday)
     * @agent-pattern: Simplified GROUP BY aggregation
     */
    public function getRevenueByPeriod(string $period, string $range, ?int $branchId = null): array
    {
        [$startDate, $endDate] = $this->getDateRangeFromFilter($range);
        
        // Determine grouping expression based on period
        $groupExpr = match($period) {
            'hour' => 'HOUR(issue_date)',
            'weekday' => 'DAYOFWEEK(issue_date)',
            default => 'DATE(issue_date)',
        };
        
        $labelExpr = match($period) {
            'hour' => 'HOUR(issue_date) as label',
            'weekday' => 'DAYOFWEEK(issue_date) as label',
            default => 'DATE(issue_date) as label',
        };
        
        $builder = $this->db->table('invoices')
            ->select($labelExpr)
            ->select('SUM(total) as net_revenue')
            ->where('DATE(issue_date) >=', $startDate)
            ->where('DATE(issue_date) <=', $endDate);
        
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        
        $builder->groupBy($groupExpr)
                ->orderBy('label', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get top products by metric
     * @agent-pattern: Ranking query with JOIN and aggregation
     */
    public function getTopProducts(string $metric, string $range, int $limit = 10): array
    {
        [$startDate, $endDate] = $this->getDateRangeFromFilter($range);
        
        $this->db->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        
        $builder = $this->db->table('order_items oi')
            ->select('oi.product_id as id')
            ->select('p.name')
            ->select('SUM(oi.quantity * oi.final_price) as value')
            ->join('orders o', 'o.id = oi.order_id', 'inner')
            ->join('products p', 'p.id = oi.product_id', 'inner')
            ->where('DATE(o.order_date) >=', $startDate)
            ->where('DATE(o.order_date) <=', $endDate)
            ->where('o.status !=', 'cancelled')
            ->groupBy('oi.product_id')
            ->orderBy('value', 'DESC')
            ->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get top customers by purchase amount
     * @agent-pattern: Customer ranking with aggregation
     */
    public function getTopCustomers(string $range, int $limit = 10): array
    {
        [$startDate, $endDate] = $this->getDateRangeFromFilter($range);
        
        $this->db->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        
        $builder = $this->db->table('orders o')
            ->select('o.customer_id as id')
            ->select('c.name')
            ->select('SUM(o.total) as value')
            ->join('customers c', 'c.id = o.customer_id', 'inner')
            ->where('DATE(o.order_date) >=', $startDate)
            ->where('DATE(o.order_date) <=', $endDate)
            ->where('o.customer_id IS NOT NULL')
            ->groupBy('o.customer_id')
            ->orderBy('value', 'DESC')
            ->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    public function getRecentActivities(int $limit = 15): array
    {
        $sql = "
            (SELECT 
                CONCAT('inv-', i.id) as id,
                'invoice' as type,
                COALESCE(u.username, 'System') as username,
                'Bán đơn hàng' as action,
                i.total as amount,
                i.issue_date as timestamp,
                CONCAT('INV-', i.id) as related_code,
                CONCAT('#/invoices/', i.id) as linked_page
            FROM invoices i
            LEFT JOIN users u ON u.id = i.created_by
            WHERE (i.invoice_status != 'cancelled' OR i.invoice_status IS NULL)
            ORDER BY i.issue_date DESC
            LIMIT {$limit})
            
            UNION ALL
            
            (SELECT 
                CONCAT('ret-', r.id) as id,
                'return' as type,
                COALESCE(u.username, 'System') as username,
                'Nhận trả hàng' as action,
                COALESCE(r.return_amount, 0) as amount,
                r.updated_at as timestamp,
                r.return_number as related_code,
                CONCAT('#/returns/', r.id) as linked_page
            FROM returns r
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.status = 'completed'
            ORDER BY r.updated_at DESC
            LIMIT {$limit})
            
            UNION ALL
            
            (SELECT 
                CONCAT('dn-', d.id) as id,
                'delivery' as type,
                'System' as username,
                'Giao hàng' as action,
                0 as amount,
                d.delivery_date as timestamp,
                d.delivery_number as related_code,
                CONCAT('#/deliveries/', d.id) as linked_page
            FROM delivery_notes d
            ORDER BY d.delivery_date DESC
            LIMIT {$limit})
            
            ORDER BY timestamp DESC
            LIMIT {$limit}
        ";
        
        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Helper: Convert range filter to date range
     * @agent-pattern: Date calculation helper
     */
    private function getDateRangeFromFilter(string $range): array
    {
        $today = date('Y-m-d');
        
        return match($range) {
            'today' => [
                $today,
                $today
            ],
            'week' => [
                date('Y-m-d', strtotime('monday this week')),
                date('Y-m-d', strtotime('sunday this week'))
            ],
            'month' => [
                date('Y-m-01'), // First day of current month
                date('Y-m-t')   // Last day of current month
            ],
            'last_month' => [
                date('Y-m-01', strtotime('first day of last month')), // First day of last month
                date('Y-m-t', strtotime('last day of last month'))     // Last day of last month
            ],
            'year' => [
                date('Y-01-01'),
                date('Y-12-31')
            ],
            default => [
                date('Y-m-01'),
                date('Y-m-t')
            ]
        };
    }

    /**
     * Get daily revenue with invoice details for each day
     * @agent-pattern: Grouped data with nested details
     */
    public function getDailyRevenueWithInvoices(string $range, ?int $branchId = null): array
    {
        [$startDate, $endDate] = $this->getDateRangeFromFilter($range);
        
        // Step 1: Get daily totals
        $dailyTotals = [];
        
        // Get revenue by day from invoices
        $revenueBuilder = $this->db->table('invoices')
            ->select('DATE(issue_date) as date, SUM(total) as revenue')
            ->where('DATE(issue_date) >=', $startDate)
            ->where('DATE(issue_date) <=', $endDate)
            ->groupBy('DATE(issue_date)')
            ->orderBy('date', 'DESC');
        
        if ($branchId) {
            $revenueBuilder->where('branch_id', $branchId);
        }
        
        $revenueData = $revenueBuilder->get()->getResultArray();
        
        foreach ($revenueData as $row) {
            $dailyTotals[$row['date']] = [
                'date' => $row['date'],
                'revenue' => (float)$row['revenue'],
                'returns' => 0,
                'invoices' => [],
            ];
        }
        
        // Step 2: Get returns by day
        // Note: returns table doesn't have branch_id column, so we can't filter by branch
        $returnsBuilder = $this->db->table('returns')
            ->select('DATE(created_at) as date, SUM(return_amount) as returns')
            ->where('DATE(created_at) >=', $startDate)
            ->where('DATE(created_at) <=', $endDate)
            ->where('status', 'completed')
            ->groupBy('DATE(created_at)');
        
        $returnsData = $returnsBuilder->get()->getResultArray();
        
        foreach ($returnsData as $row) {
            if (isset($dailyTotals[$row['date']])) {
                $dailyTotals[$row['date']]['returns'] = (float)$row['returns'];
            } else {
                $dailyTotals[$row['date']] = [
                    'date' => $row['date'],
                    'revenue' => 0,
                    'returns' => (float)$row['returns'],
                    'invoices' => [],
                ];
            }
        }
        
        // Step 3: Get invoices with details for each day
        $invoiceBuilder = $this->db->table('invoices i')
            ->select('i.id, i.invoice_number, i.issue_date, i.total, c.name as customer_name')
            ->select('DATE(i.issue_date) as date')
            ->join('customers c', 'c.id = i.customer_id', 'left')
            ->where('DATE(i.issue_date) >=', $startDate)
            ->where('DATE(i.issue_date) <=', $endDate)
            ->orderBy('i.issue_date', 'DESC');
        
        if ($branchId) {
            $invoiceBuilder->where('i.branch_id', $branchId);
        }
        
        $invoices = $invoiceBuilder->get()->getResultArray();
        
        foreach ($invoices as $invoice) {
            $date = $invoice['date'];
            if (isset($dailyTotals[$date])) {
                $dailyTotals[$date]['invoices'][] = [
                    'id' => $invoice['invoice_number'] ?: 'HD' . str_pad($invoice['id'], 6, '0', STR_PAD_LEFT),
                    'time' => date('d/m/Y H:i', strtotime($invoice['issue_date'])),
                    'customer' => $invoice['customer_name'] ?: 'Khách lẻ',
                    'revenue' => (float)$invoice['total'],
                ];
            }
        }
        
        // Sort by date descending and return as array
        krsort($dailyTotals);
        return array_values($dailyTotals);
    }
}
