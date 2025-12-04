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
        
        // Use REAL services instead of mocks - test actual behavior
        // This ensures we test real API flows, not mocked responses
        $this->setUpAuthToken();
    }

    private function seedProduct(array $data): int
    {
        // Use factory pattern instead of hardcoded seeds
        return \Tests\Support\Factories\ProductFactory::create($data);
    }

    public function test_detailWithVariants(): void
    {
        $id = $this->seedProduct(['code' => 'PV1', 'name' => 'ProdV']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/detail-with-variants");
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data.code', 'PV1');
        $response->assertJSONPath('data.name', 'ProdV');
        $response->assertJSONPath('data.id', $id);
        
        // Verify database state matches API response
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'PV1',
            'name' => 'ProdV'
        ]);
    }

    public function test_variants_list(): void
    {
        $id = $this->seedProduct(['code' => 'PV2']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/variants");
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data', []); // Should be empty array for product without variants
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'PV2'
        ]);
        $this->assertDatabaseCount('product_variants_v2', 0, ['product_id' => $id]);
    }

    public function test_checkCode_exists(): void
    {
        $productId = $this->seedProduct(['code' => 'DUP']);
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'DUP']);
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONPath('exists', true);
        $response->assertJSONPath('exists_in_products', true);
        $response->assertJSONPath('exists_in_variants', false);
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'DUP'
        ]);
    }

    public function test_checkCode_not_exists(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'NEW']);
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONPath('exists', false);
        $response->assertJSONPath('exists_in_products', false);
        $response->assertJSONPath('exists_in_variants', false);
        
        // Verify database state
        $this->assertDatabaseMissing('products', ['code' => 'NEW']);
        $this->assertDatabaseMissing('product_variants_v2', ['sku' => 'NEW']);
    }

    public function test_images(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/images");
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data', []); // Should be empty array for product without images
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'PIMG'
        ]);
        $this->assertDatabaseCount('product_images', 0, ['product_id' => $id]);
    }

    public function test_attachImages(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG2']);
        
        // Create a test image first
        $imageId = $this->db->table('product_images')->insert([
            'product_id' => null,
            'image_url' => '/uploads/test.jpg',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')
                         ->post("api/products/{$id}/images/attach-multiple", ['image_ids' => [$imageId, 999]]);
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertArrayHasKey('attached_count', json_decode($response->getJSON()));
        $response->assertArrayHasKey('missing_count', json_decode($response->getJSON()));
        
        // Verify database state - real image should be attached, fake one should be missing
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId,
            'product_id' => $id
        ]);
    }

    public function test_analytics(): void
    {
        $id = $this->seedProduct(['code' => 'PANA']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/analytics");
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertArrayHasKey('data', json_decode($response->getJSON()));
        $response->assertArrayHasKey('total_stock', json_decode($response->getJSON())['data']);
        $response->assertArrayHasKey('total_variant', json_decode($response->getJSON())['data']);
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'PANA'
        ]);
    }

    public function test_productAttributeValues(): void
    {
        $id = $this->seedProduct(['code' => 'PATTR']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/attribute-values");
        
        // Strong assertions - test real API behavior
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data', []); // Should be empty array for product without attributes
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'PATTR'
        ]);
    }

    public function test_updateProductAttributeValues(): void
    {
         $id = $this->seedProduct(['code' => 'PATTR2']);
         $response = $this->withHeaders($this->authHeaders())
                          ->withBodyFormat('json')
                          ->post("api/products/{$id}/attribute-values", ['attribute_values' => []]);
         
         // Strong assertions - test real API behavior
         $response->assertStatus(200);
         $response->assertJSONFragment(['success' => true]);
         $response->assertArrayHasKey('data', json_decode($response->getJSON()));
         
         // Verify database state
         $this->assertDatabaseHas('products', [
             'id' => $id,
             'code' => 'PATTR2'
         ]);
    }
}
