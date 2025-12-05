<?php

namespace Tests\Services;

use App\Services\Accounting\LandedCostService;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\PurchaseOrders\PurchaseOrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: LandedCostService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class LandedCostServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private PurchaseOrderService $poService;
    private GoodsReceiptService $grnService;
    private LandedCostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->poService = new PurchaseOrderService();
        $this->grnService = new GoodsReceiptService();
        $this->service = new LandedCostService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_landed_cost_voucher()
    {
        $po = $this->poService->create([
            'branch_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 1, 'rate' => 100]],
        ])['data'];
        $this->poService->submit($po['id']);
        $grn = $this->grnService->create([
            'purchase_order_id' => $po['id'],
            'branch_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 1, 'rate' => 100]],
        ])['data'];

        $lcv = $this->service->create([
            'goods_receipt_id' => $grn['id'],
            'items' => [
                ['goods_receipt_item_id' => $grn['items'][0]['id'], 'cost_component' => 'Freight', 'amount' => 20],
            ],
        ]);

        $this->assertTrue($lcv['success']);
        $this->assertEquals(20.0, $lcv['data']['total_cost']);
    }
}
