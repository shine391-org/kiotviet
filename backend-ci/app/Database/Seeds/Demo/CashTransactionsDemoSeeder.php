<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * Seed demo cash transactions linked to real orders/returns.
 * Destructive for demo rows only: guarded by environment and selective delete.
 *
 * @agent-seeder: Cash transactions demo data
 * @agent-pattern: Selective delete + linked references
 * @agent-reusable: MEDIUM
 */
class CashTransactionsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            echo "      ⚠️  CashTransactionsDemoSeeder skipped in production\n";
            return;
        }

        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }

        echo "   → Demo cash transactions...\n";

        // Chỉ xóa dữ liệu demo (tham chiếu đơn/return demo hoặc mô tả chứa Demo)
        $this->db->table('cash_transactions')
            ->groupStart()
                ->like('reference_code', 'DH-DEMO-', 'after')
                ->orLike('reference_code', 'RET-DEMO-', 'after')
                ->orLike('description', 'demo', 'both')
                ->orWhereIn('created_by', [1, 2]) // demo users
            ->groupEnd()
            ->delete();

        $orders = DemoOrderHelper::orders($this->db);
        $orders = array_filter($orders, static fn ($row) => ($row['status'] ?? '') !== 'cancelled');
        ksort($orders);
        $returns = $this->fetchReturns();
        $customers = $this->customerMap();
        $now = Time::now()->toDateTimeString();

        $rows = [];
        foreach ($orders as $order) {
            $total = round((float) ($order['total'] ?? 0), 2);
            // Phiếu thu phải dựa trên paid_amount (số đã thanh toán), không phải total
            $paidAmount = round((float) ($order['paid_amount'] ?? 0), 2);
            if ($paidAmount <= 0) {
                continue;
            }
            $rows[] = $this->makeRow([
                'type' => 'RECEIPT',
                'amount' => $paidAmount,
                'category' => 'sales',
                'payment_method' => $this->normalizeMethod($order['payment_method'] ?? 'CASH'),
                'account_name' => 'Quỹ demo',
                'description' => 'Thu đơn ' . ($order['order_number'] ?? '') . ' (' . ($order['payment_status'] ?? 'unpaid') . ')',
                'reference_type' => 'order',
                'reference_id' => (int) $order['id'],
                'reference_code' => $order['order_number'] ?? null,
                'branch_id' => $order['branch_id'] ?? 1,
                'created_by' => 1,
                'created_by_name' => 'Demo Admin',
                'staff_name' => 'demo-staff',
                'payer_code' => 'CUST-' . ($order['customer_id'] ?? 'NA'),
                'payer_name' => $customers[$order['customer_id']]['name'] ?? 'Khách demo',
                'payer_phone' => $customers[$order['customer_id']]['phone'] ?? null,
                'payer_address' => $customers[$order['customer_id']]['address'] ?? null,
                'transaction_date' => $order['order_date'] ?? date('Y-m-d'),
                'note' => $paidAmount + 0.01 >= $total ? 'Thanh toán đủ' : 'Thanh toán một phần',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($returns as $ret) {
            $refundAmount = round((float) $ret['refund_amount'], 2);
            if ($refundAmount <= 0) {
                continue;
            }
            $rows[] = $this->makeRow([
                'type' => 'PAYMENT',
                'amount' => $refundAmount,
                'category' => 'refund',
                'payment_method' => $this->normalizeMethod($ret['refund_method'] ?? 'CASH'),
                'account_name' => 'Quỹ demo',
                'description' => 'Hoàn tiền trả hàng ' . $ret['return_number'],
                'reference_type' => 'return_order',
                'reference_id' => $ret['id'],
                'reference_code' => $ret['return_number'],
                'branch_id' => $ret['branch_id'] ?? 1,
                'created_by' => 2,
                'created_by_name' => 'Demo Manager',
                'staff_name' => 'demo-staff',
                'payer_code' => 'CUST-' . ($ret['customer_id'] ?? 'NA'),
                'payer_name' => $customers[$ret['customer_id']]['name'] ?? 'Khách demo',
                'payer_phone' => $customers[$ret['customer_id']]['phone'] ?? null,
                'payer_address' => $customers[$ret['customer_id']]['address'] ?? null,
                'transaction_date' => $ret['created_at'] ?? date('Y-m-d'),
                'note' => 'Hoàn tiền theo phiếu trả hàng',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! empty($rows)) {
            $this->db->table('cash_transactions')->insertBatch($rows);
        }
    }

    private function fetchReturns(): array
    {
        if (! $this->db->tableExists('returns')) {
            return [];
        }

        $rows = $this->db->table('returns r')
            ->select('r.id, r.return_number, r.customer_id, r.refund_amount, r.refund_method, r.created_at, o.branch_id')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->like('r.return_number', 'RET-DEMO-', 'after')
            ->whereIn('r.status', ['approved', 'completed'])
            ->get()
            ->getResultArray();

        return array_map(static function ($row) {
            $row['id'] = (int) $row['id'];
            $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
            return $row;
        }, $rows);
    }

    private function customerMap(): array
    {
        if (! $this->db->tableExists('customers')) {
            return [];
        }
        $rows = $this->db->table('customers')->select('id, name, phone, address')->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'name' => $row['name'] ?? 'Khách demo',
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
            ];
        }
        return $map;
    }

    private function makeRow(array $row): array
    {
        return array_merge([
            'description' => null,
            'reference_type' => null,
            'reference_id' => null,
            'reference_code' => null,
            'payment_method' => null,
            'status' => 'approved',
            'account_name' => null,
            'bank_account' => null,
            'created_by_name' => null,
            'staff_name' => null,
            'payer_code' => null,
            'payer_name' => null,
            'payer_phone' => null,
            'payer_address' => null,
            'transfer_note' => null,
            'note' => null,
            'transaction_date' => date('Y-m-d'),
        ], $row);
    }

    private function normalizeMethod(?string $method): string
    {
        $upper = strtoupper($method ?? '');
        if ($upper === 'E_WALLET') {
            return 'EWALLET';
        }
        $allowed = ['CASH', 'BANK_TRANSFER', 'CARD', 'COD', 'EWALLET'];
        return in_array($upper, $allowed, true) ? $upper : 'CASH';
    }
}
