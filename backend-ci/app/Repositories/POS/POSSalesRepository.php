<?php

namespace App\Repositories\POS;

use CodeIgniter\Database\BaseConnection;

/**
 * POS Sales data access - quick return queries, daily report, sellers
 *
 * @agent-repository: POSSales
 * @agent-pattern: Repository pattern
 */
class POSSalesRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Find invoice by ID with basic info */
    public function findInvoiceById(int $id): ?array
    {
        return $this->db->table('invoices')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /** Find order ID linked to invoice */
    public function findOrderIdByInvoice(int $invoiceId): ?int
    {
        $row = $this->db->table('invoice_orders')
            ->select('order_id')
            ->where('invoice_id', $invoiceId)
            ->get()
            ->getRowArray();
        return $row ? (int) $row['order_id'] : null;
    }

    /** Find order with items */
    public function findOrderWithItems(int $orderId): ?array
    {
        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->get()
            ->getRowArray();

        if (!$order) {
            return null;
        }

        $order['items'] = $this->db->table('order_items')
            ->where('order_id', $orderId)
            ->get()
            ->getResultArray();

        return $order;
    }

    /** Get sales summary for a date */
    public function getSalesSummary(int $branchId, string $date, ?int $userId = null): array
    {
        $builder = $this->db->table('orders o')
            ->select('
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(oi.quantity), 0) as total_items,
                COALESCE(SUM(o.subtotal), 0) as gross_sales,
                COALESCE(SUM(o.discount_total), 0) as discounts,
                COALESCE(SUM(o.total), 0) as net_sales
            ')
            ->join('order_items oi', 'oi.order_id = o.id', 'left')
            ->where('DATE(o.created_at)', $date)
            ->where('o.status', 'completed');

        if ($branchId > 0) {
            $builder->where('o.branch_id', $branchId);
        }
        if ($userId) {
            $builder->where('o.created_by', $userId);
        }

        $row = $builder->get()->getRowArray();

        $totalOrders = (int) ($row['total_orders'] ?? 0);
        $netSales = (float) ($row['net_sales'] ?? 0);

        return [
            'total_orders' => $totalOrders,
            'total_items' => (int) ($row['total_items'] ?? 0),
            'gross_sales' => (float) ($row['gross_sales'] ?? 0),
            'discounts' => (float) ($row['discounts'] ?? 0),
            'net_sales' => $netSales,
            'average_order_value' => $totalOrders > 0 ? round($netSales / $totalOrders, 2) : 0,
        ];
    }

    /** Get returns summary for a date */
    public function getReturnsSummary(int $branchId, string $date, ?int $userId = null): array
    {
        $builder = $this->db->table('returns r')
            ->select('
                COUNT(r.id) as total_returns,
                COALESCE(SUM(r.return_amount), 0) as return_amount,
                COALESCE(SUM(r.refund_amount), 0) as refund_amount
            ')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->where('DATE(r.created_at)', $date)
            ->whereIn('r.status', ['approved', 'completed']);

        if ($branchId > 0) {
            $builder->where('o.branch_id', $branchId);
        }
        if ($userId) {
            $builder->where('r.created_by', $userId);
        }

        return $builder->get()->getRowArray() ?? [];
    }

    /** Get payment methods breakdown */
    public function getPaymentBreakdown(int $branchId, string $date, ?int $userId = null): array
    {
        $builder = $this->db->table('order_payments op')
            ->select('op.payment_method as method, COUNT(*) as count, SUM(op.amount) as total')
            ->join('orders o', 'o.id = op.order_id', 'inner')
            ->where('DATE(op.paid_at)', $date)
            ->where('o.status', 'completed')
            ->groupBy('op.payment_method');

        if ($branchId > 0) {
            $builder->where('o.branch_id', $branchId);
        }
        if ($userId) {
            $builder->where('o.created_by', $userId);
        }

        $rows = $builder->get()->getResultArray();

        return array_map(fn($r) => [
            'method' => $r['method'] ?? 'unknown',
            'count' => (int) $r['count'],
            'total' => (float) $r['total'],
        ], $rows);
    }

    /** Get top selling products */
    public function getTopProducts(int $branchId, string $date, int $limit = 10): array
    {
        $builder = $this->db->table('order_items oi')
            ->select('
                oi.product_id,
                p.name as product_name,
                p.code as product_code,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * oi.final_price) as revenue
            ')
            ->join('orders o', 'o.id = oi.order_id', 'inner')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->where('DATE(o.created_at)', $date)
            ->where('o.status', 'completed')
            ->groupBy('oi.product_id')
            ->orderBy('quantity_sold', 'DESC')
            ->limit($limit);

        if ($branchId > 0) {
            $builder->where('o.branch_id', $branchId);
        }

        $rows = $builder->get()->getResultArray();

        return array_map(fn($r) => [
            'product_id' => (int) $r['product_id'],
            'product_name' => $r['product_name'],
            'product_code' => $r['product_code'],
            'quantity_sold' => (int) ($r['quantity_sold'] ?? 0),
            'revenue' => (float) ($r['revenue'] ?? 0),
        ], $rows);
    }

    /** Get hourly sales data */
    public function getHourlySales(int $branchId, string $date): array
    {
        $builder = $this->db->table('orders')
            ->select('HOUR(created_at) as hour, COUNT(*) as orders, SUM(total) as revenue')
            ->where('DATE(created_at)', $date)
            ->where('status', 'completed')
            ->groupBy('HOUR(created_at)')
            ->orderBy('hour', 'ASC');

        if ($branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        $rows = $builder->get()->getResultArray();

        // Fill all 24 hours
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyData[$h] = ['hour' => $h, 'orders' => 0, 'revenue' => 0];
        }
        foreach ($rows as $r) {
            $h = (int) $r['hour'];
            $hourlyData[$h] = [
                'hour' => $h,
                'orders' => (int) $r['orders'],
                'revenue' => (float) $r['revenue'],
            ];
        }

        return array_values($hourlyData);
    }

    /** Get cash drawer movements for a date */
    public function getCashMovements(int $branchId, string $date): array
    {
        // Opening balance from POS shifts
        $shiftRow = $this->db->table('pos_shifts')
            ->select('opening_balance')
            ->where('branch_id', $branchId)
            ->where('DATE(opened_at)', $date)
            ->orderBy('opened_at', 'ASC')
            ->get()
            ->getRowArray();

        $openingBalance = (float) ($shiftRow['opening_balance'] ?? 0);

        // Cash transactions
        $cashIn = $this->db->table('cash_transactions')
            ->selectSum('amount')
            ->where('branch_id', $branchId)
            ->where('DATE(transaction_date)', $date)
            ->where('type', 'receipt')
            ->get()
            ->getRowArray();

        $cashOut = $this->db->table('cash_transactions')
            ->selectSum('amount')
            ->where('branch_id', $branchId)
            ->where('DATE(transaction_date)', $date)
            ->where('type', 'payment')
            ->get()
            ->getRowArray();

        $cashInAmount = (float) ($cashIn['amount'] ?? 0);
        $cashOutAmount = (float) ($cashOut['amount'] ?? 0);

        return [
            'opening_balance' => $openingBalance,
            'cash_in' => $cashInAmount,
            'cash_out' => $cashOutAmount,
            'expected_balance' => $openingBalance + $cashInAmount - $cashOutAmount,
        ];
    }

    /** Find sellers (users who can sell) */
    public function findSellers(?int $branchId, ?string $search, string $status = 'active'): array
    {
        $builder = $this->db->table('users')
            ->select('id, username, full_name, phone, branch_id')
            ->where('status', $status);

        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }

        if ($search) {
            $builder->groupStart()
                ->like('full_name', $search)
                ->orLike('username', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        $builder->orderBy('full_name', 'ASC')
            ->limit(50);

        return $builder->get()->getResultArray();
    }
}
