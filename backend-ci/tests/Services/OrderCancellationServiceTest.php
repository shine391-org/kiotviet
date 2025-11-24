<?php

namespace Tests\Services;

use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderStatusService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\StatusSchemaTrait;

/** @agent-test: OrderCancellationService @agent-pattern: Cancel flow test */
class OrderCancellationServiceTest extends CIUnitTestCase
{
    use StatusSchemaTrait;

    protected $db;
    private OrderCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Requires sqlite for fast unit test');
        }
        $config = config('Database');
        $config->tests = [
            'DBDriver'    => 'SQLite3',
            'database'    => ':memory:',
            'DBPrefix'    => 'db_',
            'foreignKeys' => true,
            'DBDebug'     => true,
        ];
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        $this->resetStatusSchema();
        $this->seedStock(1, 10, 3);

        $statusService = service('orderStatusService');
        $this->service = new OrderCancellationService($statusService);
    }

    /** @test */
    public function it_cancels_from_processing_and_restores_stock()
    {
        $orderId = $this->seedOrder('processing');
        $this->seedItems($orderId, [['product_id' => 10, 'variant_id' => null, 'quantity' => 2]]);

        $res = $this->service->cancel($orderId, 'customer cancel');
        $this->assertTrue($res['success']);
        $this->assertEquals('cancelled', $res['data']['status']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 10)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $stock['quantity_on_hand']);
    }

    /** @test */
    public function it_blocks_cancellation_from_completed()
    {
        $orderId = $this->seedOrder('completed');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->cancel($orderId, 'late cancel');
    }

    private function seedOrder(string $status): int
    {
        $this->db->table('orders')->insert([
            'order_number' => 'ORD-' . rand(100, 999),
            'customer_id' => 1,
            'branch_id' => 1,
            'status' => $status,
            'order_type' => 'shipping',
            'payment_method' => 'BANK_TRANSFER',
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
            'shipping_fee' => 0,
            'paid_amount' => 0,
            'debt_amount' => 0,
            'is_paid' => 0,
        ]);
        return (int) $this->db->insertID();
    }

    private function seedItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $this->db->table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'base_price' => 0,
                'final_price' => 0,
            ]);
        }
    }

    private function seedStock(int $branchId, int $productId, float $qty): void
    {
        $this->db->table('inventory_stock')->insert([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
        ]);
    }
}
