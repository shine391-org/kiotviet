<?php

namespace Tests\Services;

use App\Services\PurchaseOrders\PurchaseOrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PurchaseOrderService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PurchaseOrderServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private PurchaseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new PurchaseOrderService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_submits_purchase_order()
    {
        $po = $this->service->create([
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'rate' => 100],
            ],
        ])['data'];

        $this->assertEquals('draft', $po['status']);
        $this->assertEquals(200.0, $po['total']);

        $submitted = $this->service->submit($po['id']);
        $this->assertEquals('submitted', $submitted['data']['status']);
    }
}
