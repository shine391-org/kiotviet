<?php

namespace Tests\Services;

use App\Services\Inventory\GoodsReceiptService;
use App\Services\PurchaseOrders\PurchaseOrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: GoodsReceiptService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class GoodsReceiptServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private GoodsReceiptService $service;
    private PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->poService = new PurchaseOrderService();
        $this->service = new GoodsReceiptService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_goods_receipt_and_updates_po()
    {
        $po = $this->poService->create([
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 5, 'rate' => 10],
            ],
        ])['data'];
        $this->poService->submit($po['id']);

        $grn = $this->service->create([
            'purchase_order_id' => $po['id'],
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 3, 'rate' => 10],
            ],
        ])['data'];

        $this->assertEquals('submitted', $grn['status']);
        $updatedPo = $this->poService->get($po['id'])['data'];
        $this->assertEquals(3.0, $updatedPo['items'][0]['received_quantity']);
    }
}
