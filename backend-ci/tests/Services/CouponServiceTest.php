<?php

namespace Tests\Services;

use App\Services\Coupons\CouponService;
use App\Repositories\Coupons\CouponRepository;
use App\Validators\CouponValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CouponService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class CouponServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        $this->setUpDatabase();
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();
        $this->seedCoupons();
        $this->service = new CouponService(
            new CouponRepository(null, null, $this->db),
            new CouponValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_percent_coupon()
    {
        $query = $this->db->table('coupons')->get();
        $err = $this->db->error();
        if (! empty($err['code'])) {
            $this->fail('DB error: ' . $err['message']);
        }
        $all = $query->getResultArray();
        $this->assertNotEmpty($all, 'seeded coupons missing');
        $res = $this->service->apply('SALE10', 200);
        $this->assertTrue($res['success']);
        $this->assertEquals(20.0, $res['discount']);
    }

    /** @test */
    public function it_blocks_expired_coupon()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->apply('OLD', 100);
    }

    private function seedCoupons(): void
    {
        $this->ensureCouponTables();
        $now = date('Y-m-d H:i:s');
        $fields = $this->db->getFieldNames('coupons');
        $rows = [
            [
                'code' => 'SALE10',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_amount' => 0,
                'expiry_date' => null,
                'usage_limit' => 0,
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'OLD',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_amount' => 0,
                'expiry_date' => date('Y-m-d', strtotime('-1 day')),
                'usage_limit' => 0,
                'used_count' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        // align rows to existing columns
        $aligned = array_map(function ($row) use ($fields) {
            return array_intersect_key($row, array_flip($fields));
        }, $rows);
        $result = $this->db->table('coupons')->insertBatch($aligned);
        $err = $this->db->error();
        if ($result === false || ! empty($err['code'])) {
            throw new \RuntimeException('Seed coupons failed: ' . ($err['message'] ?? 'unknown'));
        }
        if ($this->db->affectedRows() <= 0) {
            throw new \RuntimeException('Seed coupons failed: no rows inserted');
        }
    }

    private function ensureCouponTables(): void
    {
        $existing = array_flip($this->db->listTables());
        if (! isset($existing['coupons'])) {
            $this->db->query("CREATE TABLE IF NOT EXISTS coupons (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(100) NOT NULL UNIQUE,
                discount_type VARCHAR(20) DEFAULT 'percent',
                discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
                min_amount DECIMAL(14,2) DEFAULT 0,
                expiry_date DATE NULL,
                usage_limit INT DEFAULT 0,
                used_count INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $err = $this->db->error();
            if (! empty($err['code'])) {
                throw new \RuntimeException('Create coupons failed: ' . $err['message']);
            }
        }
        if (! isset($existing['coupon_usages'])) {
            $this->db->query("CREATE TABLE IF NOT EXISTS coupon_usages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                coupon_id BIGINT UNSIGNED NOT NULL,
                order_id BIGINT UNSIGNED NULL,
                customer_id BIGINT UNSIGNED NULL,
                used_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $err = $this->db->error();
            if (! empty($err['code'])) {
                throw new \RuntimeException('Create coupon usages failed: ' . $err['message']);
            }
        }
    }
}
