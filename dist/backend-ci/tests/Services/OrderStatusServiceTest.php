<?php

namespace Tests\Services;

use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Orders\OrderPaymentRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Inventory\InventoryMovementRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderStatusTransition;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/** @agent-test: OrderStatusService @agent-pattern: Status workflow test */
class OrderStatusServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected $db;
    private OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->resetCompleteSchema();

        $orders = new OrderRepository(null, null, $this->db);
        $logs = new OrderStatusLogRepository(null, $this->db);
        $transition = new OrderStatusTransition();
        $paymentRepo = new OrderPaymentRepository($this->db);
        $inventoryRepo = new InventoryRepository(null, $this->db);
        $movementRepo = new InventoryMovementRepository(null, $this->db);
        $logger = new InventoryMovementLogger($movementRepo);

        $this->service = new OrderStatusService($orders, $transition, $logs, $paymentRepo, $inventoryRepo, $logger, null, null, null);
    }

    /** @test */
    public function it_allows_valid_transition_and_logs()
    {
        $orderId = $this->seedOrder('draft');

        $res = $this->service->updateStatus($orderId, 'confirmed', 1, 'auto');

        $this->assertTrue($res['success']);
        $this->assertEquals('confirmed', $res['data']['status']);

        $logCount = $this->db->table('order_status_logs')->where('order_id', $orderId)->countAllResults();
        $this->assertEquals(1, $logCount);
    }

    /** @test */
    public function it_deducts_inventory_when_processing()
    {
        $orderId = $this->seedOrder('confirmed');
        $this->seedItems($orderId, [
            ['product_id' => 10, 'variant_id' => null, 'quantity' => 2, 'final_price' => 100],
        ]);
        $this->seedStock(branchId: 1, productId: 10, qty: 5);

        $res = $this->service->updateStatus($orderId, 'processing', 2, 'go processing');
        $this->assertTrue($res['success']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 10)->get()->getRowArray();
        $this->assertEquals(3.0, (float) $stock['quantity_on_hand']);

        $movement = $this->db->table('inventory_movements')->where('reference_id', $orderId)->get()->getRowArray();
        $this->assertEquals(-2.0, (float) $movement['quantity']);
    }

    /** @test */
    public function it_restores_inventory_when_cancel_after_processing()
    {
        $orderId = $this->seedOrder('processing');
        $this->seedItems($orderId, [
            ['product_id' => 10, 'variant_id' => null, 'quantity' => 2, 'final_price' => 100],
        ]);
        $this->seedStock(branchId: 1, productId: 10, qty: 3); // already deducted, so currently 3

        $res = $this->service->updateStatus($orderId, 'cancelled', 3, 'customer asked');
        $this->assertTrue($res['success']);
        $stock = $this->db->table('inventory_stock')->where('product_id', 10)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $stock['quantity_on_hand']);
    }

    /** @test */
    public function it_creates_cash_receipt_when_completed_cash_order()
    {
        $orderId = $this->seedOrder('delivered', paymentMethod: 'CASH', total: 150000, branchId: 1);

        $res = $this->service->updateStatus($orderId, 'completed', 1);

        $this->assertTrue($res['success']);

        $cashRow = $this->db->table('cash_transactions')
            ->where('reference_type', 'order')
            ->where('reference_id', $orderId)
            ->get()->getRowArray();

        $this->assertNotNull($cashRow, 'Cash receipt should be created for completed cash order');
        $this->assertEquals('RECEIPT', $cashRow['type']);
        $this->assertEquals(150000.00, (float) $cashRow['amount']);
    }

    private function seedOrder(string $status, string $paymentMethod = 'BANK_TRANSFER', float $total = 0, int $branchId = 1): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'order_number' => 'ORD-' . rand(100, 999),
            'customer_id' => 1,
            'branch_id' => $branchId,
            'status' => $status,
            'order_type' => 'shipping',
            'payment_method' => $paymentMethod,
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'paid_amount' => $paymentMethod === 'CASH' ? $total : 0,
            'debt_amount' => 0,
            'is_paid' => 0,
            'created_at' => $now,
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
                'base_price' => $item['final_price'],
                'final_price' => $item['final_price'],
            ]);
        }
    }

    private function seedStock(int $branchId, int $productId, float $qty): void
    {
        $this->db->table('inventory_stock')->insert([
            'branch_id' => $branchId,
            'warehouse_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
