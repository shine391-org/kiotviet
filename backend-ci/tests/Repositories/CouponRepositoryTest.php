<?php

namespace Tests\Repositories;

use App\Repositories\Coupons\CouponRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CouponSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CouponRepository
 * @agent-pattern: Repository test with DevDatabaseTrait + CouponSchemaTrait
 */
class CouponRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CouponSchemaTrait;

    private CouponRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCouponSchema();
        $this->repo = new CouponRepository();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testFindAllFiltersByStatusBranchAndSearch(): void
    {
        $this->createCoupon(['code' => 'ALPHA', 'name' => 'Alpha Voucher', 'status' => 'active', 'branch_id' => 1]);
        $this->createCoupon(['code' => 'BETA', 'name' => 'Beta Voucher', 'status' => 'active', 'branch_id' => 2]);
        $this->createCoupon(['code' => 'GAMMA', 'name' => 'Gamma Voucher', 'status' => 'inactive', 'branch_id' => 1]);

        $filters = ['status' => 'active', 'branch_id' => 1, 'search' => 'alp', 'limit' => 10, 'page' => 1];
        $result = $this->repo->findAll($filters);

        $this->assertCount(1, $result);
        $this->assertSame('ALPHA', $result[0]['code']);
        $this->assertSame(1, $this->repo->count($filters));
    }

    public function testCreateWithSensitiveFieldsPersistsProtectedColumns(): void
    {
        $coupon = $this->repo->createWithSensitiveFields([
            'code' => 'SENS-001',
            'discount_type' => 'percent',
            'discount_value' => 12.5,
            'status' => 'active',
            'name' => 'Sensitive Coupon',
        ], [
            'used_count' => 3,
            'branch_id' => 7,
            'customer_group_id' => 9,
            'creator_id' => 2,
        ]);

        $this->assertNotNull($coupon);
        $this->assertSame(3, (int) $coupon['used_count']);
        $this->assertSame(7, (int) $coupon['branch_id']);
        $this->assertSame(9, (int) $coupon['customer_group_id']);
        $this->assertSame(2, (int) $coupon['creator_id']);
    }

    public function testUpdateSensitiveFieldsUpdatesOnlyAllowedKeys(): void
    {
        $coupon = $this->createCoupon([
            'code' => 'UPD-SENSITIVE',
            'status' => 'active',
            'branch_id' => 4,
            'used_count' => 1,
        ]);

        $this->repo->updateSensitiveFields($coupon['id'], [
            'used_count' => 5,
            'branch_id' => 8,
            'customer_group_id' => 11,
            'status' => 'inactive',
            'unknown_key' => 'ignored',
        ]);

        $updated = $this->repo->findById($coupon['id']);

        $this->assertSame(5, (int) $updated['used_count']);
        $this->assertSame(8, (int) $updated['branch_id']);
        $this->assertSame(11, (int) $updated['customer_group_id']);
        $this->assertSame('active', $updated['status']);
        $this->assertArrayNotHasKey('unknown_key', $updated);
    }

    public function testUsageTrackingIncrementsAndCountsByCustomer(): void
    {
        $coupon = $this->createCoupon(['code' => 'TRACK', 'used_count' => 0]);

        $this->repo->incrementUsage($coupon['id']);
        $afterIncrement = $this->repo->findById($coupon['id']);
        $this->assertSame(1, (int) $afterIncrement['used_count']);

        $this->repo->recordUsage($coupon['id'], 101, 201);
        $this->repo->recordUsage($coupon['id'], 102, 202);
        $this->repo->recordUsage($coupon['id'], 103, 201);

        $this->assertSame(3, $this->repo->getUsageCount($coupon['id']));
        $this->assertSame(2, $this->repo->getUsageCount($coupon['id'], 201));
        $this->assertSame(1, $this->repo->getUsageCount($coupon['id'], 202));
    }
}
