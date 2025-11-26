<?php

namespace Tests\Services;

use App\Services\PurchaseOrders\PurchaseOrderStatusService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PurchaseOrderStatusService
 */
class PurchaseOrderStatusServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PurchaseOrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new PurchaseOrderStatusService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_cash_payment_on_received_cash_po()
    {
        $poId = $this->seedPO(branchId: 2, total: 500000, paymentMethod: 'CASH');

        $res = $this->service->markReceived($poId, 1);

        $this->assertTrue($res['success']);
        $this->assertEquals('received', $res['data']['status']);

        $tx = $this->db->table('cash_transactions')->where('reference_type', 'purchase_order')->where('reference_id', $poId)->get()->getRowArray();
        $this->assertNotNull($tx);
        $this->assertEquals('PAYMENT', $tx['type']);
        $this->assertEquals(500000.00, (float) $tx['amount']);
        $this->assertEquals(2, (int) $tx['branch_id']);
    }

    /** @test */
    public function it_skips_cash_payment_when_payment_method_not_cash()
    {
        $poId = $this->seedPO(branchId: 1, total: 100000, paymentMethod: 'BANK_TRANSFER');
        $res = $this->service->markReceived($poId, 1);
        $this->assertTrue($res['success']);
        $this->assertNull($res['transaction']);
        $this->assertEquals(0, $this->db->table('cash_transactions')->countAllResults());
    }

    private function seedPO(int $branchId, float $total, string $paymentMethod): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('purchase_orders')->insert([
            'po_number' => 'PO' . rand(1000, 9999),
            'code' => 'POC' . rand(1000, 9999),
            'branch_id' => $branchId,
            'payment_method' => $paymentMethod,
            'total' => $total,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }
}
