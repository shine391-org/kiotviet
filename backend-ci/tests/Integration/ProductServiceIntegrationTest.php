<?php

namespace Tests\Integration;

use App\Services\Products\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;

class ProductServiceIntegrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        $this->productService = new ProductService(null, null, null, $this->db);
    }

    public function test_create_product_success(): void
    {
        $data = [
            'code' => 'P001',
            'name' => 'Test Product',
            'selling_price' => 100000,
            'product_type' => 'goods'
        ];

        $result = $this->productService->create($data);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('P001', $result['data']['code']);

        $inDb = $this->db->table('products')->where('code', 'P001')->get()->getRowArray();
        $this->assertNotNull($inDb);
        $this->assertEquals('Test Product', $inDb['name']);
    }

    public function test_create_product_duplicate_code_fails(): void
    {
        $this->seedProduct(['code' => 'P001', 'name' => 'Existing']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách sản phẩm');

        $this->productService->create(['code' => 'P001', 'name' => 'Duplicate']);
    }

    public function test_update_product_success(): void
    {
        $id = $this->seedProduct(['code' => 'P002', 'name' => 'Old Name']);

        $result = $this->productService->update($id, ['name' => 'New Name']);

        $this->assertTrue($result['success']);
        $inDb = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertEquals('New Name', $inDb['name']);
        $this->assertEquals('P002', $inDb['code']);
    }

    public function test_delete_product_success(): void
    {
        $id = $this->seedProduct(['code' => 'P003']);

        $result = $this->productService->delete($id);

        $this->assertTrue($result['success']);
        $inDb = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($inDb);
        $this->assertNotNull($inDb['deleted_at']);
    }

    public function test_list_products(): void
    {
        $this->seedProduct(['code' => 'A1', 'name' => 'Apple']);
        $this->seedProduct(['code' => 'B1', 'name' => 'Banana']);

        $result = $this->productService->list(['search' => 'Apple']);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Apple', $result['data'][0]['name']);
    }

    public function test_get_product_details(): void
    {
         $id = $this->seedProduct(['code' => 'P-DETAIL', 'name' => 'Detail Product']);

         $result = $this->productService->get($id);

         $this->assertTrue($result['success']);
         $this->assertEquals($id, $result['data']['id']);
    }

    private function seedProduct(array $data): int
    {
        $payload = array_merge([
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Sample',
            'product_type' => 'goods',
            'status' => 'active',
            'selling_price' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);

        $this->db->table('products')->insert($payload);
        return (int) $this->db->insertID();
    }
}
