<?php

namespace Tests\Services;

use App\Services\Shipping\ShippingCODSettlementService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: ShippingCODSettlementService
 */
class ShippingCODSettlementServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ShippingCODSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new ShippingCODSettlementService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_receipt_when_cod_greater_than_fee()
    {
        $orderId = $this->seedCodOrder(status: 'delivered', total: 300000, branchId: 1);

        $res = $this->service->settleCOD(1, [$orderId], 100000, 1);

        $this->assertTrue($res['success']);
        $tx = $this->db->table('cash_transactions')->get()->getRowArray();
        $this->assertEquals('RECEIPT', $tx['type']);
        $this->assertEquals(200000.00, (float) $tx['amount']);
        $this->assertEquals('shipping_cod', $tx['category']);
    }

    /** @test */
    public function it_creates_payment_when_fee_greater_than_cod()
    {
        $orderId = $this->seedCodOrder(status: 'completed', total: 100000, branchId: 2);

        $res = $this->service->settleCOD(2, [$orderId], 150000, 1);

        $this->assertTrue($res['success']);
        $tx = $this->db->table('cash_transactions')->get()->getRowArray();
        $this->assertEquals('PAYMENT', $tx['type']);
        $this->assertEquals(50000.00, (float) $tx['amount']);
        $this->assertEquals('shipping_fee', $tx['category']);
        $this->assertEquals(2, (int) $tx['branch_id']);
    }

    private function seedCodOrder(string $status, float $total, int $branchId): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'order_number' => 'COD-' . rand(1000, 9999),
            'customer_id' => 1,
            'branch_id' => $branchId,
            'status' => $status,
            'payment_method' => 'COD',
            'total' => $total,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }
}
