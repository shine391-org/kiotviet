<?php

namespace Tests\Services;

use App\Services\Loyalty\LoyaltyService;
use App\Repositories\Loyalty\LoyaltyRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: LoyaltyService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class LoyaltyServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->seedProgram();
        $this->service = new LoyaltyService(new LoyaltyRepository(null, null, null, $this->db));
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_earns_and_redeems_points()
    {
        $earned = $this->service->earn(1, 1000, 10);
        $this->assertGreaterThan(0, $earned);

        $res = $this->service->redeem(1, (int) $earned, 11);
        $this->assertEquals($earned, $res['points_used']);

        $wallet = $this->db->table('loyalty_wallets')->where('customer_id', 1)->get()->getRowArray();
        $this->assertEquals(0.0, (float) $wallet['points_balance']);
    }

    /** @test */
    public function it_blocks_insufficient_points()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->redeem(1, 10, null);
    }

    private function seedProgram(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('loyalty_programs')->insert([
            'name' => 'Default',
            'earn_rate' => 0.01, // 1% point per currency
            'redeem_rate' => 1,  // 1 currency per point
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
