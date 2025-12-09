<?php

namespace Tests\Services;

use App\Repositories\Loyalty\LoyaltyRepository;
use App\Services\Loyalty\LoyaltyService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\LoyaltySchemaTrait;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: LoyaltyService (DB)
 * @agent-pattern: Service test with DevDatabaseTrait + LoyaltySchemaTrait
 */
class LoyaltyServiceDbTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use LoyaltySchemaTrait;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetLoyaltySchema();

        $repo = new LoyaltyRepository(null, null, null, $this->db);
        $this->service = new LoyaltyService($repo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testGetWalletCreatesNewWallet(): void
    {
        $customer = $this->createCustomerForLoyalty();

        $result = $this->service->getWallet($customer['id']);

        $this->assertTrue($result['success']);
        $this->assertSame($customer['id'], $result['data']['customer_id']);
        $this->assertSame(0.0, $result['data']['points_balance']);
    }

    public function testGetWalletThrowsWhenCustomerNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->getWallet(99999);
    }

    public function testCalculateEarnPointsWithoutProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();

        $result = $this->service->calculateEarnPoints($customer['id'], 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['data']['points_to_earn']);
        $this->assertSame('No active loyalty program', $result['data']['message']);
    }

    public function testCalculateEarnPointsWithProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyProgram(['earn_rate' => 0.01]); // 1 point per 100 VND

        $result = $this->service->calculateEarnPoints($customer['id'], 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(10000.0, (float) $result['data']['points_to_earn']);
    }

    public function testRedeemPreviewThrowsWhenNoWallet(): void
    {
        $customer = $this->createCustomerForLoyalty();

        $this->expectException(InvalidArgumentException::class);
        $this->service->redeemPreview($customer['id'], 100);
    }

    public function testRedeemPreviewThrowsWhenInsufficientPoints(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyWallet($customer['id'], ['points_balance' => 50]);
        $this->createLoyaltyProgram();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient points');
        $this->service->redeemPreview($customer['id'], 100);
    }

    public function testRedeemPreviewThrowsWhenNoProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyWallet($customer['id'], ['points_balance' => 1000]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No active loyalty program');
        $this->service->redeemPreview($customer['id'], 100);
    }

    public function testRedeemPreviewCalculatesDiscount(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyWallet($customer['id'], ['points_balance' => 1000]);
        $this->createLoyaltyProgram(['redeem_rate' => 100]); // 1 point = 100 VND

        $result = $this->service->redeemPreview($customer['id'], 100);

        $this->assertTrue($result['success']);
        $this->assertSame(100.0, $result['data']['points_to_redeem']);
        $this->assertSame(10000, (int) $result['data']['discount_amount']);
        $this->assertSame(900.0, $result['data']['remaining_points']);
    }

    public function testEarnPointsWithoutProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();

        $result = $this->service->earnPoints($customer['id'], 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['data']['points_earned']);
    }

    public function testEarnPointsWithProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyProgram(['earn_rate' => 0.01]);

        $result = $this->service->earnPoints($customer['id'], 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(10000.0, (float) $result['data']['points_earned']);
    }

    public function testRedeemPointsThrowsWhenInsufficientPoints(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyWallet($customer['id'], ['points_balance' => 50]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->redeemPoints($customer['id'], 100);
    }

    public function testGetTransactionsReturnsEmptyWhenNoWallet(): void
    {
        $customer = $this->createCustomerForLoyalty();

        $result = $this->service->getTransactions($customer['id']);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['data']);
    }

    public function testGetWalletReturnsExistingWallet(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $this->createLoyaltyWallet($customer['id'], ['points_balance' => 500]);

        $result = $this->service->getWallet($customer['id']);

        $this->assertTrue($result['success']);
        $this->assertSame(500.0, $result['data']['points_balance']);
    }

    public function testGetWalletReturnsProgram(): void
    {
        $customer = $this->createCustomerForLoyalty();
        $program = $this->createLoyaltyProgram();

        $result = $this->service->getWallet($customer['id']);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']['program']);
        $this->assertSame($program['name'], $result['data']['program']['name']);
    }
}
