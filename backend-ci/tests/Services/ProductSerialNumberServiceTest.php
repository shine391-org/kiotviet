<?php

namespace Tests\Services;

use App\Repositories\Products\ProductSerialNumberRepository;
use App\Services\Products\ProductSerialNumberService;
use App\Validators\ProductSerialValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductBatchSerialSchemaTrait;

/**
 * @agent-test: ProductSerialNumberService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class ProductSerialNumberServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductBatchSerialSchemaTrait;

    private ProductSerialNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductBatchSerialSchema();
        $repo = new ProductSerialNumberRepository(null, $this->db);
        $this->service = new ProductSerialNumberService($repo, new ProductSerialValidator());
        $this->seedProduct();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_serial_number()
    {
        $res = $this->service->create([
            'product_id' => 1,
            'serial_number' => 'SN-001',
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals('SN-001', $res['data']['serial_number']);
    }

    /** @test */
    public function it_prevents_duplicate_serial_number()
    {
        $this->service->create([
            'product_id' => 1,
            'serial_number' => 'DUP-001',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([
            'product_id' => 1,
            'serial_number' => 'DUP-001',
        ]);
    }

    /** @test */
    public function it_reserves_and_releases_serials()
    {
        $this->service->create([
            'product_id' => 1,
            'serial_number' => 'RSV-01',
        ]);

        $reserve = $this->service->reserve([
            'serial_numbers' => ['RSV-01'],
            'order_id' => 9,
        ]);
        $this->assertTrue($reserve['success']);
        $row = $this->db->table('product_serial_numbers')->where('serial_number', 'RSV-01')->get()->getRowArray();
        $this->assertEquals('reserved', $row['status']);
        $this->assertEquals(9, (int) $row['reserved_for_order_id']);

        $this->service->release(['RSV-01']);
        $row = $this->db->table('product_serial_numbers')->where('serial_number', 'RSV-01')->get()->getRowArray();
        $this->assertEquals('available', $row['status']);
    }

    /** @test */
    public function it_marks_serial_sold_and_returned()
    {
        $this->service->create([
            'product_id' => 1,
            'serial_number' => 'SALE-1',
        ]);

        $sell = $this->service->sell([
            'serial_numbers' => ['SALE-1'],
            'order_id' => 5,
        ]);
        $this->assertTrue($sell['success']);

        $row = $this->db->table('product_serial_numbers')->where('serial_number', 'SALE-1')->get()->getRowArray();
        $this->assertEquals('sold', $row['status']);
        $this->assertEquals(5, (int) $row['sold_to_order_id']);

        $return = $this->service->markReturned([
            'serial_numbers' => ['SALE-1'],
            'order_id' => 5,
        ]);
        $this->assertTrue($return['success']);
        $row = $this->db->table('product_serial_numbers')->where('serial_number', 'SALE-1')->get()->getRowArray();
        $this->assertEquals('returned', $row['status']);
    }

    private function seedProduct(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'SN-P',
            'name' => 'Serial Product',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
