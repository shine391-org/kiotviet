<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use DateTimeImmutable;

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

        echo "   → Demo invoices...\n";

        $this->cleanupExisting();

        $orders = DemoOrderHelper::orders($this->db, ['completed']);
        $orders = $this->filterTaxCustomers($orders);
        $taxTemplates = $this->taxTemplates();
        ksort($orders);
        if (empty($orders)) {
            echo "      ⚠️  No completed orders with tax code found for invoices\n";
            return;
        }

        $orderIds = array_map(static fn ($row) => (int) $row['id'], array_values($orders));
        $orderItems = DemoOrderHelper::orderItemsByOrder($this->db, $orderIds);

        $branchCounters = [];
        $invoiceRows = [];
        $links = [];
        $now = date('Y-m-d H:i:s');
        $today = new DateTimeImmutable('today');

        $idx = 0;
        foreach ($orders as $order) {
            $branchId = (int) ($order['branch_id'] ?? 1);
            $branchCounters[$branchId] = ($branchCounters[$branchId] ?? 0) + 1;
            $invoiceNumber = sprintf('HD-DEMO-%d-%04d', $branchId, $branchCounters[$branchId]);
            $issueOffset = max(0, 10 - $idx);
            $issueDate = $today->modify('-' . $issueOffset . ' days');
            $dueDate = $issueDate->modify('+12 days');
            $summary = $this->summarizeOrder($order, $orderItems[$order['id']] ?? []);
            [$vatRate, $vatAmount] = $this->resolveVat($order, $summary, $taxTemplates, $idx);
            $total = $summary['order_total'] > 0
                ? (float) $summary['order_total']
                : round($summary['net_before_vat'] + $vatAmount, 2);
            $paidAmount = min((float) ($order['paid_amount'] ?? 0), $total);
            $paymentStatus = $this->paymentStatus($total, $paidAmount);

            // Shipment data for shipments page
            $shippingPartners = ['GHTK', 'GHN', 'VNPost', 'J&T Express', 'Viettel Post'];
            $deliveryStatuses = ['pending', 'shipping', 'delivered', 'failed', 'returning'];
            $deliveryStatus = $deliveryStatuses[$idx % count($deliveryStatuses)];
            $shippingPartner = $shippingPartners[$idx % count($shippingPartners)];
            $shipmentCode = sprintf('VĐ-DEMO-%d-%04d', $branchId, $branchCounters[$branchId]);
            $deliveryTime = $deliveryStatus === 'delivered' ? $dueDate->format('Y-m-d H:i:s') : null;

            $invoiceRows[] = [
                'invoice_number' => $invoiceNumber,
                'customer_id' => $order['customer_id'] ?? null,
                'branch_id' => $branchId,
                'issue_date' => $issueDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'subtotal' => $summary['net_before_vat'],
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'tax_amount' => $vatAmount,
                'total' => $total,
                'goods_total' => $summary['goods_total'],
                'discount_total' => $summary['discount_total'],
                'net_total' => $summary['net_before_vat'],
                'other_fee' => 0,
                'shipping_fee' => $summary['shipping_fee'],
                'customer_payable' => $total,
                'customer_paid' => $paidAmount,
                'cod_amount' => max($total - $paidAmount, 0),
                'rounding_adjustment' => 0,
                'payment_status' => $paymentStatus,
                'total_paid' => $paidAmount,
                'invoice_status' => 'completed',
                'invoice_type' => 'standard',
                // Shipment columns for shipments page
                'shipment_code' => $shipmentCode,
                'delivery_status' => $deliveryStatus,
                'shipping_partner' => $shippingPartner,
                'delivery_time' => $deliveryTime,
                'delivery_note' => $deliveryStatus === 'delivered' ? 'Đã giao thành công' : null,
                'pdf_path' => null,
                'notes' => 'Hóa đơn demo gắn với ' . ($order['order_number'] ?? 'đơn hàng'),
                'meta' => json_encode(['source' => 'demo']),
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $links[] = [
                'invoice_number' => $invoiceNumber,
                'order_id' => (int) $order['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $idx++;
        }

        $invoiceIdMap = [];
        foreach ($invoiceRows as $row) {
            $this->db->table('invoices')->insert($row);
            $invoiceIdMap[$row['invoice_number']] = (int) $this->db->insertID();
        }

        if (! empty($links) && $this->db->tableExists('invoice_orders')) {
            $rows = [];
            foreach ($links as $link) {
                $invoiceId = $invoiceIdMap[$link['invoice_number']] ?? null;
                if (! $invoiceId) {
                    continue;
                }
                $rows[] = [
                    'invoice_id' => $invoiceId,
                    'order_id' => $link['order_id'],
                    'created_at' => $link['created_at'],
                    'updated_at' => $link['updated_at'],
                ];
            }
            if (! empty($rows)) {
                $this->db->table('invoice_orders')->insertBatch($rows);
            }
        }
    }

    private function filterTaxCustomers(array $orders): array
    {
        // NOTE: Previously filtered for customers with tax_code only.
        // Removed that restriction so invoices are created for all customers
        // to ensure customers visible in UI have invoices in their detail panels.
        if (! $this->db->tableExists('customers')) {
            return $orders;
        }
        $customerIds = array_unique(array_column($orders, 'customer_id'));
        if (empty($customerIds)) {
            return [];
        }
        // Return all orders that have a valid customer_id in the customers table
        $customers = $this->db->table('customers')
            ->select('id')
            ->whereIn('id', $customerIds)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();
        $validIds = array_map(static fn ($row) => (int) $row['id'], $customers);
        return array_filter($orders, static fn ($o) => in_array((int) $o['customer_id'], $validIds, true));
    }

    private function summarizeOrder(array $order, array $items): array
    {
        // Ưu tiên lấy trực tiếp từ order để tránh sai lệch làm trôi total
        $goods = isset($order['subtotal']) ? (float) $order['subtotal'] : 0.0;
        $discount = isset($order['discount_total']) ? (float) $order['discount_total'] : 0.0;
        $shipping = isset($order['shipping_fee']) ? (float) $order['shipping_fee'] : 0.0;

        // Net trước VAT = (subtotal - discount) + shipping
        $netBeforeVat = round(max(0, $goods - $discount) + $shipping, 2);
        $orderTotal = isset($order['total']) ? (float) $order['total'] : 0.0;
        $orderTax = isset($order['tax_total']) ? (float) $order['tax_total'] : 0.0;

        // Nếu đã có total trong order thì khóa theo total để invoice khớp 1:1
        if ($orderTotal > 0) {
            $netBeforeVat = max(0, round($orderTotal - $orderTax, 2));
        }

        return [
            'goods_total' => round($goods, 2),
            'discount_total' => round($discount, 2),
            'shipping_fee' => $shipping,
            'net_before_vat' => $netBeforeVat,
            'order_total' => $orderTotal,
            'order_tax' => $orderTax,
        ];
    }

    private function taxTemplates(): array
    {
        if (! $this->db->tableExists('tax_templates')) {
            return [];
        }
        $rows = $this->db->table('tax_templates')->select('id, rate_percent')->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $ratePercent = (float) ($row['rate_percent'] ?? 0);
            $map[$id] = [
                'rate_percent' => $ratePercent,
                'rate_fraction' => $ratePercent / 100,
            ];
        }
        return $map;
    }

    private function resolveVat(array $order, array $summary, array $taxTemplates, int $idx): array
    {
        $net = (float) $summary['net_before_vat'];
        $vatAmount = (float) ($summary['order_tax'] ?? ($order['tax_total'] ?? 0.0));
        $vatRate = 0.0;

        if (! empty($order['tax_template_id']) && isset($taxTemplates[$order['tax_template_id']])) {
            $vatRate = (float) $taxTemplates[$order['tax_template_id']]['rate_fraction'];
        } elseif ($net > 0 && $vatAmount > 0) {
            $vatRate = round($vatAmount / $net, 4);
        }

        if ($vatAmount <= 0 && $vatRate > 0 && $net > 0) {
            $vatAmount = round($net * $vatRate, 2);
        }

        // Fallback to demo rotation if still zero
        if ($vatAmount <= 0 && $net > 0) {
            $vatRate = $this->vatRateForIndex($idx);
            $vatAmount = round($net * $vatRate, 2);
        }

        if ($vatRate <= 0 && $net > 0 && $vatAmount > 0) {
            $vatRate = round($vatAmount / $net, 4);
        }

        return [$vatRate, $vatAmount];
    }

    private function vatRateForIndex(int $idx): float
    {
        $rates = [0.0, 0.05, 0.1];
        return $rates[$idx % count($rates)];
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }
        if ($paid + 0.01 >= $total) {
            return 'paid';
        }
        return 'partial';
    }

    private function cleanupExisting(): void
    {
        $existing = $this->db->table('invoices')
            ->select('id')
            ->like('invoice_number', 'HD-DEMO-', 'after')
            ->get()
            ->getResultArray();
        if (! empty($existing) && $this->db->tableExists('invoice_orders')) {
            $ids = array_map(static fn ($row) => (int) $row['id'], $existing);
            $this->db->table('invoice_orders')->whereIn('invoice_id', $ids)->delete();
        }
        if (! empty($existing)) {
            $ids = array_map(static fn ($row) => (int) $row['id'], $existing);
            $this->db->table('invoices')->whereIn('id', $ids)->delete();
        }
    }
}
