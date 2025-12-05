<?php

namespace Tests\Services;

use App\Repositories\Manufacturing\BOMRepository;
use App\Services\Manufacturing\BOMService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ManufacturingSchemaTrait;

/**
 * @agent-test: BOMService
 * @agent-pattern: Service test with DevDatabaseTrait + schema reset
 */
class BOMServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ManufacturingSchemaTrait;

    private BOMService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetManufacturingSchema();
        $this->seedProducts();
        $this->service = new BOMService(new BOMRepository(null, null, $this->db));
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_create_bom_with_components(): void
    {
        $result = $this->service->create([
            'product_id' => 100,
            'version' => 'v1',
            'quantity' => 1,
            'items' => [
                ['component_product_id' => 1, 'quantity' => 2],
                ['component_product_id' => 2, 'quantity' => 1.5],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        $this->assertCount(2, $result['data']['items']);
    }

    public function test_reject_duplicate_component(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'product_id' => 100,
            'items' => [
                ['component_product_id' => 1, 'quantity' => 1],
                ['component_product_id' => 1, 'quantity' => 2],
            ],
        ]);
    }

    public function test_update_bom_replaces_items(): void
    {
        $created = $this->service->create([
            'product_id' => 100,
            'items' => [
                ['component_product_id' => 1, 'quantity' => 1],
            ],
        ]);

        $updated = $this->service->update($created['data']['id'], [
            'items' => [
                ['component_product_id' => 2, 'quantity' => 3],
            ],
        ]);

        $this->assertCount(1, $updated['data']['items']);
        $this->assertSame(2, (int) $updated['data']['items'][0]['component_product_id']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insertBatch([
            ['id' => 100, 'code' => 'FG', 'name' => 'Finished', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 1, 'code' => 'C1', 'name' => 'Comp1', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'C2', 'name' => 'Comp2', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
