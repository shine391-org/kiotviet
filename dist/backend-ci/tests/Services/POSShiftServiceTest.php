<?php

namespace Tests\Services;

use App\Services\POS\POSShiftService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POSShiftService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + POSSchemaTrait
 */
class POSShiftServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use POSSchemaTrait;

    private POSShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->seedBase();
        $this->service = new POSShiftService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_opens_and_blocks_multiple_shifts()
    {
        $open = $this->service->open([
            'user_id' => 1,
            'branch_id' => 1,
            'opening_balance' => 100000,
        ]);
        $this->assertTrue($open['success']);
        $this->assertEquals('open', $open['data']['status']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->open([
            'user_id' => 1,
            'branch_id' => 1,
            'opening_balance' => 0,
        ]);
    }

    /** @test */
    public function it_records_payments_and_closes_with_discrepancy()
    {
        $shift = $this->service->open([
            'user_id' => 2,
            'branch_id' => 1,
            'opening_balance' => 0,
        ]);
        $shiftId = $shift['data']['id'];

        $this->service->recordPayment($shiftId, [
            'payment_method' => 'CASH',
            'amount' => 100000,
            'order_id' => 10,
        ]);

        $row = $this->db->table('pos_shifts')->where('id', $shiftId)->get()->getRowArray();
        $this->assertEquals(100000.0, (float) $row['expected_total']);
        $this->assertEquals(100000.0, (float) $row['expected_cash']);

        $close = $this->service->close([
            'shift_id' => $shiftId,
            'actual_payments' => [
                'CASH' => 90000,
            ],
            'closing_note' => 'Counted cash',
        ]);

        $this->assertTrue($close['success']);
        $closed = $this->db->table('pos_shifts')->where('id', $shiftId)->get()->getRowArray();
        $this->assertEquals('closed', $closed['status']);
        $this->assertEquals(90000.0, (float) $closed['actual_total']);
        $this->assertEquals(-10000.0, round((float) $closed['discrepancy'], 2));
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }
}
