<?php

namespace Tests\Services;

use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderStatusService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\StatusSchemaTrait;

/**
 * @agent-test: OrderCancellationService
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class OrderCancellationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use StatusSchemaTrait;

    private OrderCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetStatusSchema();
        
        // Create database connection without prefix for status tables
        $dbWithoutPrefix = \Config\Database::connect('tests');
        $dbWithoutPrefix->setPrefix('');
        
        $this->seedStock($dbWithoutPrefix, 1, 10, 3);

        $statusService = service('orderStatusService');
        $this->service = new OrderCancellationService($statusService);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_cancels_from_processing_and_restores_stock()
    {
        $orderId = $this->seedOrder('processing');
        $this->seedItems($orderId, [['product_id' => 10, 'variant_id' => null, 'quantity' => 2]]);

        $res = $this->service->cancel($orderId, 'customer cancel');
        $this->assertTrue($res['success']);
        $this->assertEquals('cancelled', $res['data']['status']);

        $dbWithoutPrefix = \Config\Database::connect('tests');
        $dbWithoutPrefix->setPrefix('');
        $stock = $dbWithoutPrefix->table('inventory_stock')->where('product_id', 10)->get()->getRowArray();
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
        $dbWithoutPrefix = \Config\Database::connect('tests');
        $dbWithoutPrefix->setPrefix('');
        
        $dbWithoutPrefix->table('orders')->insert([
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
        return (int) $dbWithoutPrefix->insertID();
    }

    private function seedItems(int $orderId, array $items): void
    {
        $dbWithoutPrefix = \Config\Database::connect('tests');
        $dbWithoutPrefix->setPrefix('');
        
        foreach ($items as $item) {
            $dbWithoutPrefix->table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'base_price' => 0,
                'final_price' => 0,
            ]);
        }
    }

    private function seedStock($db, int $branchId, int $productId, float $qty): void
    {
        $db->table('inventory_stock')->insert([
            'branch_id' => $branchId,
            'warehouse_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
        ]);
    }
}
