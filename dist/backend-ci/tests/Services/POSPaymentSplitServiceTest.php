<?php

namespace Tests\Services;

use App\Services\POS\POSPaymentSplitService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: POSPaymentSplitService validation
 * @agent-pattern: Pure service test
 */
class POSPaymentSplitServiceTest extends CIUnitTestCase
{
    private POSPaymentSplitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new POSPaymentSplitService();
    }

    /** @test */
    public function it_accepts_allowed_methods_and_matching_total()
    {
        $result = $this->service->validate(
            [
                ['payment_method' => 'cash', 'amount' => 60],
                ['payment_method' => 'CARD', 'amount' => 40],
            ],
            ['CASH', 'CARD'],
            100
        );

        $this->assertCount(2, $result);
        $this->assertEquals('CASH', $result[0]['payment_method']);
        $this->assertEquals(100, array_sum(array_column($result, 'amount')));
    }

    /** @test */
    public function it_blocks_disallowed_methods()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->validate(
            [['payment_method' => 'VOUCHER', 'amount' => 10]],
            ['CASH'],
            10
        );
    }
}
