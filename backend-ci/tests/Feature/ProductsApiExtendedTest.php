<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

class ProductsApiExtendedTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use CompleteSchemaTrait;
    use DevDatabaseTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        // Reset shared services so repositories reconnect to tests DB
        \Config\Services::reset(true);
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        // Force default DB group to tests for API calls
        $config = config('Database');
        $config->defaultGroup = 'tests';
        // Inject mock productService to avoid heavy deps
        $mock = $this->createMock(\App\Services\Products\ProductService::class);
        $mock->method('get')->willReturn(['success' => true, 'data' => ['code' => 'MOCK']]);
        $mock->method('variants')->willReturn(['success' => true, 'data' => []]);
        $mock->method('images')->willReturn(['success' => true, 'data' => []]);
        $mock->method('attachImages')->willReturn(['success' => true]);
        $mock->method('analytics')->willReturn(['success' => true, 'data' => []]);
        $mock->method('productAttributeValues')->willReturn(['success' => true, 'data' => []]);
        $mock->method('updateProductAttributeValues')->willReturn(['success' => true]);
        \Config\Services::injectMock('productService', $mock);
        $this->setUpAuthToken();
    }

    private function seedProduct(array $data): int
    {
        $payload = array_merge([
            'product_type' => null,
            'code' => 'P' . random_int(1000, 9999),
            'barcode' => null,
            'name' => 'Sample',
            'status' => 'active',
            'selling_price' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);

        $this->db->table('products')->insert($payload);
        return (int) $this->db->insertID();
    }

    public function test_detailWithVariants(): void
    {
        $id = $this->seedProduct(['code' => 'PV1', 'name' => 'ProdV']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/detail-with-variants");
        $response->assertStatus(200);
        $response->assertJSONPath('data.code', 'PV1');
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_variants_list(): void
    {
        $id = $this->seedProduct(['code' => 'PV2']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/variants");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_checkCode_exists(): void
    {
        $this->seedProduct(['code' => 'DUP']);
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'DUP']);
        $response->assertStatus(200);
        $response->assertJSONPath('exists', true);
    }

    public function test_checkCode_not_exists(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'NEW']);
        $response->assertStatus(200);
        $response->assertJSONPath('exists', false);
    }

    public function test_images(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/images");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_attachImages(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG2']);
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')
                         ->post("api/products/{$id}/images/attach-multiple", ['image_ids' => [999]]);
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_analytics(): void
    {
        $id = $this->seedProduct(['code' => 'PANA']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/analytics");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_productAttributeValues(): void
    {
        $id = $this->seedProduct(['code' => 'PATTR']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/attribute-values");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_updateProductAttributeValues(): void
    {
         $id = $this->seedProduct(['code' => 'PATTR2']);
         $response = $this->withHeaders($this->authHeaders())
                          ->withBodyFormat('json')
                          ->post("api/products/{$id}/attribute-values", ['attribute_values' => []]);
         $response->assertStatus(200);
         $response->assertJSONFragment(['success' => true]);
    }
}
