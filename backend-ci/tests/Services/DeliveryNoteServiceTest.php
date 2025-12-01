<?php

namespace Tests\Services;

use App\Services\DeliveryNotes\DeliveryNoteService;
use App\Repositories\DeliveryNotes\DeliveryNoteRepository;
use App\Repositories\DeliveryNotes\DeliveryNoteItemRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use App\Validators\DeliveryNoteValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: DeliveryNoteService @agent-pattern: Service test with DevDatabaseTrait */
class DeliveryNoteServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private DeliveryNoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBaseData();

        $db = $this->db;
        $repo = new DeliveryNoteRepository(null, null, $db);
        $itemRepo = new DeliveryNoteItemRepository(null, $db);
        $orderRepo = new OrderRepository(null, null, $db);
        $inventoryRepo = new InventoryRepository(null, $db);
        $movementRepo = new \App\Repositories\Inventory\InventoryMovementRepository(null, $db);
        $movementLogger = new InventoryMovementLogger($movementRepo);
        $batchService = new ProductBatchService(null, null, $inventoryRepo, $movementLogger);
        $serialService = new ProductSerialNumberService(null, null, null);

        $this->service = new DeliveryNoteService(
            $repo,
            $itemRepo,
            new DeliveryNoteValidator(),
            $orderRepo,
            $inventoryRepo,
            $movementLogger,
            $batchService,
            $serialService
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_from_order_and_confirms()
    {
        $res = $this->service->createFromOrder([
            'order_id' => 1,
            'branch_id' => 1,
            'delivery_date' => '2025-11-29',
        ]);
        $this->assertTrue($res['success']);
        $noteId = $res['data']['id'];

        $confirm = $this->service->confirm($noteId, 9);
        $this->assertEquals('confirmed', $confirm['data']['status']);
        $this->assertEquals(9, $confirm['data']['confirmed_by']);
    }

    /** @test */
    public function it_delivers_partially_and_blocks_over_delivery()
    {
        $note = $this->service->createFromOrder([
            'order_id' => 1,
            'branch_id' => 1,
        ])['data'];
        $this->service->confirm($note['id'], 1);
        $itemId = $note['items'][0]['id'];

        $deliver = $this->service->deliver($note['id'], [
            'items' => [
                ['delivery_note_item_id' => $itemId, 'quantity' => 1],
            ],
        ]);
        $this->assertEquals('shipped', $deliver['data']['status']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(4.0, (float) $stock['quantity_on_hand']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->deliver($note['id'], [
            'items' => [
                ['delivery_note_item_id' => $itemId, 'quantity' => 2], // over remaining 1
            ],
        ]);
    }

    /** @test */
    public function it_cancels_and_rollback_inventory()
    {
        $note = $this->service->createFromOrder([
            'order_id' => 1,
            'branch_id' => 1,
        ])['data'];
        $itemId = $note['items'][0]['id'];

        $this->service->confirm($note['id'], 1);
        $this->service->deliver($note['id'], [
            'items' => [
                ['delivery_note_item_id' => $itemId, 'quantity' => 2],
            ],
        ]);

        $stockAfter = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(3.0, (float) $stockAfter['quantity_on_hand']);

        $cancel = $this->service->cancel($note['id'], 1);
        $this->assertEquals('cancelled', $cancel['data']['status']);

        $stockRollback = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $stockRollback['quantity_on_hand']);
    }

    private function seedBaseData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HN', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'code' => 'WH-1', 'name' => 'Warehouse 1', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Customer 1', 'phone' => '0123456789', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Product 1',
            'selling_price' => 10000,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('orders')->insert([
            'id' => 1,
            'order_number' => 'ORD-1',
            'customer_id' => 1,
            'branch_id' => 1,
            'status' => 'confirmed',
            'order_type' => 'shipping',
            'payment_method' => 'CASH',
            'subtotal' => 20000,
            'total' => 20000,
            'paid_amount' => 0,
            'debt_amount' => 20000,
            'order_date' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('order_items')->insert([
            'id' => 1,
            'order_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'quantity' => 2,
            'base_price' => 10000,
            'final_price' => 10000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'warehouse_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
