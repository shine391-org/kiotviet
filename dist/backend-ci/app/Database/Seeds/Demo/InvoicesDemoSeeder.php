<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Seeder 20 hóa đơn demo (invoices) theo quy tắc HD-{branch}-{counter} + mapping invoice_orders.
 *
 * @agent-seeder: Invoices demo data
 * @agent-pattern: Idempotent delete-by-prefix + batch insert
 * @agent-reusable: MEDIUM
 */
class InvoicesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('invoices')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $today = new \DateTimeImmutable('today');

        // Dọn dữ liệu demo cũ
        $demoInvoices = $this->db->table('invoices')
            ->select('id')
            ->like('invoice_number', 'HD-DEMO-', 'after')
            ->get()
            ->getResultArray();
        $demoInvoiceIds = array_column($demoInvoices, 'id');

        if (! empty($demoInvoiceIds) && $this->db->tableExists('invoice_orders')) {
            $this->db->table('invoice_orders')->whereIn('invoice_id', $demoInvoiceIds)->delete();
        }
        if (! empty($demoInvoiceIds)) {
            $this->db->table('invoices')->whereIn('id', $demoInvoiceIds)->delete();
        }

        // Counter per branch cho số hóa đơn
        $counters = [1 => 1, 2 => 1];
        $nextNumber = function (int $branch) use (&$counters): string {
            $num = str_pad((string) $counters[$branch]++, 4, '0', STR_PAD_LEFT);
            return "HD-DEMO-{$branch}-{$num}";
        };

        // Danh sách khách hàng có tax_code (theo CustomersDemoSeeder: 2011-2015)
        $taxCustomers = [2011, 2012, 2013, 2014, 2015];

        $rates = [0.0, 0.05, 0.1];
        $invoices = [];

        for ($i = 0; $i < 20; $i++) {
            $branchId = ($i % 2) + 1; // 1,2
            $customerId = $taxCustomers[$i % count($taxCustomers)];
            $issueDate = $today->modify("-" . (20 - $i) . " days");
            $dueDate = $issueDate->modify('+15 days');
            $subtotal = 1200000 + ($i * 50000); // tăng nhẹ để data đa dạng
            $vatRate = $rates[$i % count($rates)];
            $vatAmount = round($subtotal * $vatRate, 2);
            $total = $subtotal + $vatAmount;

            $invoices[] = [
                'invoice_number' => $nextNumber($branchId),
                'customer_id' => $customerId,
                'branch_id' => $branchId,
                'issue_date' => $issueDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'pdf_path' => null,
                'notes' => 'Hóa đơn demo – không dùng cho môi trường thật',
                'meta' => json_encode(['source' => 'demo', 'vat_rate' => $vatRate]),
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('invoices')->insertBatch($invoices);

        // Map một số hóa đơn vào đơn hàng hoàn tất (ưu tiên tuân thủ Decision #22)
        if ($this->db->tableExists('invoice_orders') && $this->db->tableExists('orders')) {
            $orderRow = $this->db->table('orders')
                ->select('id')
                ->where('order_number', 'DH-DEMO-015') // completed + customer có tax_code
                ->get()
                ->getRowArray();

            if ($orderRow) {
                $orderId = (int) $orderRow['id'];

                // Lấy lại danh sách id hóa đơn vừa insert
                $newInvoices = $this->db->table('invoices')
                    ->select('id')
                    ->like('invoice_number', 'HD-DEMO-', 'after')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

                $mapRows = [];
                foreach ($newInvoices as $idx => $row) {
                    // Map 10 hóa đơn đầu vào order completed để FE có dữ liệu liên kết
                    if ($idx < 10) {
                        $mapRows[] = [
                            'invoice_id' => (int) $row['id'],
                            'order_id' => $orderId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($mapRows)) {
                    $this->db->table('invoice_orders')->insertBatch($mapRows);
                }
            }
        }
    }
}
