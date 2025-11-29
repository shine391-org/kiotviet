<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

class ProductsApiE2ETest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedProduct(1, ['code' => 'AO01', 'name' => 'Áo thun', 'status' => 'active']);
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_get_products_list(): void
    {
        $response = $this->withHeaders($this->authHeaders())->get('api/products');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('pagination.page', 1);
        $response->assertJSONPath('data.0.code', 'AO01');
    }

    public function test_get_products_list_search(): void
    {
        $response = $this->withHeaders($this->authHeaders())->get('api/products?search=ao');
        $response->assertStatus(200);
        $response->assertJSONPath('data.0.code', 'AO01');
    }

    public function test_get_product_detail(): void
    {
        $response = $this->withHeaders($this->authHeaders())->get('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONPath('data.id', 1);
        $response->assertJSONPath('data.code', 'AO01');
    }

    public function test_post_create_product(): void
    {
        $payload = ['code' => 'AO02', 'name' => 'Áo polo'];
        $response = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')->post('api/products');
        $response->assertStatus(201);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data.code', 'AO02');
    }

    public function test_put_update_product(): void
    {
        $response = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['name' => 'Áo updated']), 'application/json')->put('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_delete_product(): void
    {
        $response = $this->withHeaders($this->authHeaders())->delete('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    private function seedProduct(int $id, array $data): void
    {
        $payload = array_merge([
            'id' => $id,
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
    }
}
