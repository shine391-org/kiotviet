<?php

namespace Tests\Services;

use App\Repositories\Coupons\CouponRepository;
use App\Services\Coupons\CouponService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CouponSchemaTrait;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: CouponService (DB)
 * @agent-pattern: Service test with DevDatabaseTrait + CouponSchemaTrait
 */
class CouponServiceDbTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CouponSchemaTrait;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCouponSchema();

        $repo = new CouponRepository(null, null, $this->db);
        $this->service = new CouponService($repo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testListReturnsEmptyWhenNoCoupons(): void
    {
        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['data']);
        $this->assertSame(0, $result['pagination']['total']);
    }

    public function testListReturnsCoupons(): void
    {
        $this->createCoupon(['code' => 'TEST1']);
        $this->createCoupon(['code' => 'TEST2']);

        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetReturnsCoupon(): void
    {
        $coupon = $this->createCoupon(['code' => 'GETTEST']);

        $result = $this->service->get($coupon['id']);

        $this->assertTrue($result['success']);
        $this->assertSame('GETTEST', $result['data']['code']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->get(99999);
    }

    public function testCreateCoupon(): void
    {
        $result = $this->service->create([
            'code' => 'NEWCOUPON',
            'discount_type' => 'percent',
            'discount_value' => 15,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('NEWCOUPON', $result['data']['code']);
        $this->assertSame(15.0, $result['data']['discount_value']);
    }

    public function testCreateThrowsWhenCodeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['discount_value' => 10]);
    }

    public function testCreateThrowsWhenCodeExists(): void
    {
        $this->createCoupon(['code' => 'DUPLICATE']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['code' => 'DUPLICATE']);
    }

    public function testCreateUppercasesCode(): void
    {
        $result = $this->service->create([
            'code' => 'lowercase',
            'discount_value' => 10,
        ]);

        $this->assertSame('LOWERCASE', $result['data']['code']);
    }

    public function testUpdateCoupon(): void
    {
        $coupon = $this->createCoupon(['code' => 'UPDATE']);

        $result = $this->service->update($coupon['id'], [
            'discount_value' => 25,
            'status' => 'inactive',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(25.0, $result['data']['discount_value']);
        $this->assertSame('inactive', $result['data']['status']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->update(99999, ['status' => 'inactive']);
    }

    public function testDeleteCoupon(): void
    {
        $coupon = $this->createCoupon(['code' => 'DELETE']);

        $result = $this->service->delete($coupon['id']);

        $this->assertTrue($result['success']);

        $this->expectException(RuntimeException::class);
        $this->service->get($coupon['id']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->delete(99999);
    }

    public function testApplyCouponWithPercentDiscount(): void
    {
        $this->createCoupon([
            'code' => 'PERCENT10',
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);

        $result = $this->service->apply('PERCENT10', 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(100000, (int) $result['data']['discount_amount']);
        $this->assertSame(900000, (int) $result['data']['final_total']);
    }

    public function testApplyCouponWithFixedDiscount(): void
    {
        $this->createCoupon([
            'code' => 'FIXED50K',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
        ]);

        $result = $this->service->apply('FIXED50K', 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame(50000, (int) $result['data']['discount_amount']);
        $this->assertSame(950000, (int) $result['data']['final_total']);
    }

    public function testApplyThrowsWhenCouponNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->apply('NOTEXIST', 1000000);
    }

    public function testApplyThrowsWhenCouponInactive(): void
    {
        $this->createCoupon(['code' => 'INACTIVE', 'status' => 'inactive']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not active');
        $this->service->apply('INACTIVE', 1000000);
    }

    public function testApplyThrowsWhenCouponExpired(): void
    {
        $this->createCoupon([
            'code' => 'EXPIRED',
            'expiry_date' => date('Y-m-d', strtotime('-1 day')),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expired');
        $this->service->apply('EXPIRED', 1000000);
    }

    public function testApplyThrowsWhenUsageLimitReached(): void
    {
        $this->createCoupon([
            'code' => 'LIMITED',
            'usage_limit' => 1,
            'used_count' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('usage limit');
        $this->service->apply('LIMITED', 1000000);
    }

    public function testApplyThrowsWhenMinAmountNotMet(): void
    {
        $this->createCoupon([
            'code' => 'MINAMOUNT',
            'min_amount' => 500000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum amount');
        $this->service->apply('MINAMOUNT', 100000);
    }

    public function testApplyCouponCaseInsensitive(): void
    {
        $this->createCoupon(['code' => 'UPPERCASE']);

        $result = $this->service->apply('uppercase', 1000000);

        $this->assertTrue($result['success']);
        $this->assertSame('UPPERCASE', $result['data']['code']);
    }

    public function testFixedDiscountCappedAtOrderTotal(): void
    {
        $this->createCoupon([
            'code' => 'BIGDISCOUNT',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
        ]);

        $result = $this->service->apply('BIGDISCOUNT', 50000);

        $this->assertTrue($result['success']);
        $this->assertSame(50000, (int) $result['data']['discount_amount']);
        $this->assertSame(0, (int) $result['data']['final_total']);
    }

    public function testTransformIncludesIsExpired(): void
    {
        $this->createCoupon([
            'code' => 'FUTURE',
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
        ]);
        $this->createCoupon([
            'code' => 'PAST',
            'expiry_date' => date('Y-m-d', strtotime('-1 day')),
        ]);

        $result = $this->service->list([]);

        $future = array_filter($result['data'], fn($c) => $c['code'] === 'FUTURE');
        $past = array_filter($result['data'], fn($c) => $c['code'] === 'PAST');

        $this->assertFalse(array_values($future)[0]['is_expired']);
        $this->assertTrue(array_values($past)[0]['is_expired']);
    }

    public function testTransformIncludesRemainingUses(): void
    {
        $this->createCoupon([
            'code' => 'LIMITED',
            'usage_limit' => 10,
            'used_count' => 3,
        ]);

        $result = $this->service->list([]);

        $this->assertSame(7, $result['data'][0]['remaining_uses']);
    }
}
