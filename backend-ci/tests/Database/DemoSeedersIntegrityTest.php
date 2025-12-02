<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Demo seed integrity
 * @agent-pattern: Run DemoSeeder then verify references
 */
class DemoSeedersIntegrityTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedDemo();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function demo_seeders_create_consistent_links(): void
    {
        $demoOrders = $this->db->table('orders')->like('order_number', 'DH-DEMO-', 'after')->countAllResults();
        $this->assertGreaterThan(0, $demoOrders);

        $orders = $this->db->table('orders')->like('order_number', 'DH-DEMO-', 'after')->get()->getResultArray();
        $payments = $this->db->table('order_payments')->select('order_id, SUM(amount) as total')->groupBy('order_id')->get()->getResultArray();
        $paymentSum = [];
        foreach ($payments as $paymentRow) {
            $paymentSum[(int) $paymentRow['order_id']] = (float) $paymentRow['total'];
        }
        $missingPayments = [];
        foreach ($orders as $order) {
            $orderId = (int) $order['id'];
            if (! array_key_exists($orderId, $paymentSum)) {
                $missingPayments[] = $order['order_number'];
            } else {
                $this->assertEqualsWithDelta(
                    (float) ($order['paid_amount'] ?? 0),
                    $paymentSum[$orderId],
                    0.01,
                    'Order payments must sum to paid_amount for ' . $order['order_number']
                );
            }
        }
        $this->assertEmpty($missingPayments, 'All demo orders must have order_payments');

        $orphanInvoices = $this->db->table('invoice_orders io')
            ->join('invoices i', 'i.id = io.invoice_id', 'left')
            ->join('orders o', 'o.id = io.order_id', 'left')
            ->groupStart()
                ->where('i.id IS NULL', null, false)
                ->orWhere('o.id IS NULL', null, false)
            ->groupEnd()
            ->countAllResults();
        $this->assertSame(0, $orphanInvoices, 'Invoice orders must reference valid invoices and orders');

        $taxOrders = $this->db->table('orders o')
            ->select('o.id')
            ->join('customers c', 'c.id = o.customer_id', 'left')
            ->like('o.order_number', 'DH-DEMO-', 'after')
            ->where('c.tax_code IS NOT NULL', null, false)
            ->where('c.tax_code !=', '')
            ->get()
            ->getResultArray();
        if (! empty($taxOrders)) {
            $taxOrderIds = array_map(static fn ($row) => (int) $row['id'], $taxOrders);
            $invoicedOrderIds = array_map(static fn ($row) => (int) $row['order_id'], $this->db->table('invoice_orders')->whereIn('order_id', $taxOrderIds)->get()->getResultArray());
            $missingInvoices = array_diff($taxOrderIds, $invoicedOrderIds);
            $this->assertEmpty($missingInvoices, 'All tax code orders should have invoices');
        }

        $orphanReturnItems = $this->db->table('return_items ri')
            ->join('order_items oi', 'oi.id = ri.order_item_id', 'left')
            ->where('oi.id IS NULL', null, false)
            ->countAllResults();
        $this->assertSame(0, $orphanReturnItems, 'Return items must link to valid order items');

        $overReturned = $this->db->table('return_items ri')
            ->join('order_items oi', 'oi.id = ri.order_item_id', 'left')
            ->where('oi.id IS NOT NULL', null, false)
            ->where('ri.quantity_returned > oi.quantity', null, false)
            ->countAllResults();
        $this->assertSame(0, $overReturned, 'Return quantity must not exceed ordered quantity');

        $orphanDelivery = $this->db->table('delivery_note_items dni')
            ->join('delivery_notes dn', 'dn.id = dni.delivery_note_id', 'left')
            ->join('order_items oi', 'oi.id = dni.order_item_id', 'left')
            ->like('dn.delivery_number', 'DN-DEMO-', 'after')
            ->groupStart()
                ->where('dn.id IS NULL', null, false)
                ->orWhere('oi.id IS NULL', null, false)
            ->groupEnd()
            ->countAllResults();
        $this->assertSame(0, $orphanDelivery, 'Delivery items must link to notes and order items');

        $overDelivered = $this->db->table('delivery_note_items dni')
            ->join('delivery_notes dn', 'dn.id = dni.delivery_note_id', 'left')
            ->join('order_items oi', 'oi.id = dni.order_item_id', 'left')
            ->like('dn.delivery_number', 'DN-DEMO-', 'after')
            ->where('oi.id IS NOT NULL', null, false)
            ->where('dni.delivered_quantity > oi.quantity', null, false)
            ->countAllResults();
        $this->assertSame(0, $overDelivered, 'Delivered quantity must not exceed ordered quantity');

        $ordersWithoutDelivery = $this->db->table('orders o')
            ->select('o.id')
            ->like('o.order_number', 'DH-DEMO-', 'after')
            ->where('o.status !=', 'cancelled')
            ->join('delivery_notes dn', 'dn.order_id = o.id', 'left')
            ->groupBy('o.id')
            ->having('COUNT(dn.id) =', 0)
            ->countAllResults();
        $this->assertSame(0, $ordersWithoutDelivery, 'Each active demo order should have a delivery note');

        $orderTxRows = $this->db->table('cash_transactions ct')
            ->select('ct.amount, o.total')
            ->join('orders o', 'ct.reference_type = "order" AND ct.reference_id = o.id', 'inner')
            ->get()
            ->getResultArray();
        foreach ($orderTxRows as $row) {
            $this->assertEqualsWithDelta((float) $row['total'], (float) $row['amount'], 0.01, 'Order cash amount must match order total');
        }
        $nonCancelledOrders = $this->db->table('orders')->like('order_number', 'DH-DEMO-', 'after')->where('status !=', 'cancelled')->countAllResults();
        $this->assertSame($nonCancelledOrders, count($orderTxRows), 'Each active demo order should have a cash transaction');

        $returnTxRows = $this->db->table('cash_transactions ct')
            ->select('ct.amount, r.refund_amount')
            ->join('returns r', 'ct.reference_type = "return_order" AND ct.reference_id = r.id', 'inner')
            ->get()
            ->getResultArray();
        foreach ($returnTxRows as $row) {
            $this->assertEqualsWithDelta((float) $row['refund_amount'], (float) $row['amount'], 0.01, 'Return refund must match transaction amount');
        }

        $ledgerCount = $this->db->table('stock_ledgers')
            ->whereIn('reference_type', ['demo_opening', 'demo_delivery', 'demo_return'])
            ->countAllResults();
        $this->assertGreaterThan(0, $ledgerCount, 'Stock ledgers should be recorded for demo data');

        $bins = $this->db->table('stock_bins')->whereIn('product_id', [501, 502, 503])->get()->getResultArray();
        $this->assertNotEmpty($bins);
        foreach ($bins as $bin) {
            $this->assertGreaterThanOrEqual(0, (float) $bin['on_hand_qty']);
        }

        $order = $this->db->table('orders')->where('order_number', 'DH-DEMO-010')->get()->getRowArray();
        $this->assertNotNull($order);
        $items = $this->db->table('order_items')->where('order_id', $order['id'])->get()->getResultArray();
        $calcTotal = array_sum(array_map(static function ($item) {
            return (float) $item['final_price'] * (float) $item['quantity'];
        }, $items)) + (float) ($order['shipping_fee'] ?? 0);
        $this->assertEqualsWithDelta($calcTotal, (float) $order['total'], 0.01, 'Order totals should match items + shipping');
    }

    private function seedDemo(): void
    {
        // Bắt buộc dùng DB group "tests" để seed đúng database test (tránh ghi vào dev/staging).
        $seeder = \Config\Database::seeder('tests');
        ob_start();
        try {
            $seeder->call('DemoSeeder');
        } finally {
            ob_end_clean();
        }
    }
}
