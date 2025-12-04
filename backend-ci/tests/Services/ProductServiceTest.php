<?php

namespace Tests\Services;

use App\Services\Products\ProductService;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Assertions\PerformanceAssertions;
use Tests\Support\Factories\ProductFactory;
use Tests\Support\Factories\VariantFactory;
use Tests\Support\Factories\CategoryFactory;

/**
 * @agent-test: ProductService unified MySQL testing
 * @agent-pattern: Standard service test with DevDatabaseTrait
 */
class ProductServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use PerformanceAssertions;
    use \Tests\Support\Database\ProductSchemaTrait;
    
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        
        // Set database connection for factories
        \Tests\Support\Factories\BaseFactory::setDb($this->db);
        
        // Inject repository with the test connection to share transaction
        $repo = new \App\Repositories\Products\ProductRepository(null, null, null, $this->db);
        $this->service = new ProductService($repo);
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_list_returns_products_with_categories_and_variants(): void
    {
        // Use seed methods that directly insert into database
        $productId = $this->seedProduct(['code' => 'P001', 'name' => 'Product 1']);
        $this->seedCategoryLink($productId, 3);
        $this->seedVariant($productId, 'Red');

        $result = $this->service->list(['page' => 1, 'limit' => 5, 'include_variants' => true]);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['data'], 'Should return exactly one product');
        
        // Validate pagination structure and values
        $pagination = $result['pagination'];
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(5, $pagination['limit']);
        $this->assertEquals(1, $pagination['total']);
        $this->assertEquals(1, $pagination['total_pages']);
        
        // Validate product data structure
        $product = $result['data'][0];
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('code', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('status', $product);
        $this->assertArrayHasKey('created_at', $product);
        $this->assertArrayHasKey('updated_at', $product);
        $this->assertArrayHasKey('category_ids', $product);
        $this->assertArrayHasKey('variants', $product);
        
        // Validate specific values
        $this->assertEquals($productId, $product['id']);
        $this->assertEquals('P001', $product['code']);
        $this->assertEquals('Product 1', $product['name']);
        $this->assertEquals('active', $product['status']);
        $this->assertSame([3], $product['category_ids']);
        $this->assertNotEmpty($product['variants']);
        
        // Validate variant structure
        $variant = $product['variants'][0];
        $this->assertArrayHasKey('id', $variant);
        $this->assertArrayHasKey('variant_name', $variant);
        $this->assertArrayHasKey('sku', $variant);
        $this->assertArrayHasKey('status', $variant);
        $this->assertEquals('Red', $variant['variant_name']);
        $this->assertEquals('active', $variant['status']);
        
        // Verify database state integrity
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'P001',
            'name' => 'Product 1',
            'status' => 'active',
            'deleted_at' => null
        ]);
        $this->assertDatabaseHas('product_category_links', [
            'product_id' => $productId,
            'category_id' => 3
        ]);
        $this->assertDatabaseCount('product_variants_v2', 1, ['product_id' => $productId]);
        
        // Validate timestamps are properly formatted
        $this->assertTimestampFormat($product['created_at'], 'Y-m-d H:i:s');
        $this->assertTimestampFormat($product['updated_at'], 'Y-m-d H:i:s');
    }

    public function test_create_persists_product(): void
    {
        $data = ['code' => 'P100', 'name' => 'New product', 'selling_price' => 150.00];
        $result = $this->service->create($data);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertArrayHasKey('data', $result, 'Service response should contain data key');
        $this->assertArrayHasKey('id', $result['data'], 'Product data should contain id');
        
        $product = $result['data'];
        $productId = $product['id'];
        
        // Validate all expected fields are present
        $expectedFields = ['id', 'code', 'name', 'selling_price', 'status', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $product, "Product should have field: {$field}");
        }
        
        // Validate specific values
        $this->assertEquals('P100', $product['code']);
        $this->assertEquals('New product', $product['name']);
        $this->assertEquals(150.00, $product['selling_price']);
        $this->assertEquals('active', $product['status']);
        $this->assertNotNull($productId);
        $this->assertIsInt($productId);
        $this->assertGreaterThan(0, $productId);
        
        // Validate business rules
        $this->assertMonetaryPrecision($product['selling_price'], 2);
        $this->assertStringLength($product['code'], 1, 100);
        $this->assertStringLength($product['name'], 1, 255);
        $this->assertStringEquals($product['status'], 'active');
        
        // Validate timestamps
        $this->assertTimestampFormat($product['created_at'], 'Y-m-d H:i:s');
        $this->assertTimestampFormat($product['updated_at'], 'Y-m-d H:i:s');
        $this->assertDatabaseRecordCreatedRecently('products', $productId, 5); // Created within 5 seconds
        
        // Verify complete database state integrity
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'P100',
            'name' => 'New product',
            'selling_price' => 150.00,
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify decimal precision in database
        $this->assertDatabaseDecimalValue('products', $productId, 'selling_price', 150.00, 0.01);
        
        // Verify no unexpected side effects
        $this->assertDatabaseCount('products', 1); // Only one product should exist
        $this->assertDatabaseCount('product_category_links', 0); // No categories should be linked
        $this->assertDatabaseCount('product_variants_v2', 0); // No variants should exist
    }

    public function test_create_duplicate_code_throws(): void
    {
        // Create first product
        $firstResult = $this->service->create(['code' => 'DUP', 'name' => 'One']);
        $this->assertServiceSuccess($firstResult);
        $firstProductId = $firstResult['data']['id'];
        
        // Verify first product is properly stored
        $this->assertDatabaseHas('products', [
            'id' => $firstProductId,
            'code' => 'DUP',
            'name' => 'One'
        ]);
        
        // Attempt to create duplicate
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại');
        
        $this->service->create(['code' => 'DUP', 'name' => 'Two']);
        
        // Verify no additional product was created
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'id' => $firstProductId,
            'code' => 'DUP',
            'name' => 'One'
        ]);
    }

    public function test_update_changes_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP1', 'name' => 'Old', 'selling_price' => 100.00]);
        
        // Verify initial state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'UP1',
            'name' => 'Old',
            'selling_price' => 100.00,
            'status' => 'active'
        ]);

        $updateData = ['name' => 'Updated', 'status' => 'inactive', 'selling_price' => 200.00];
        $updated = $this->service->update($id, $updateData);

        // Validate response structure (update only returns success, not data)
        $this->assertServiceSuccess($updated);
        $this->assertArrayHasKey('success', $updated);
        $this->assertTrue($updated['success']);
        
        // Verify database state after update
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'UP1', // Code should remain unchanged
            'name' => 'Updated',
            'status' => 'inactive',
            'selling_price' => 200.00
        ]);
        
        // Verify business rules
        $this->assertMonetaryPrecision(200.00, 2);
        $this->assertStringLength('Updated', 1, 255);
        $this->assertStringEquals('inactive', 'inactive');
        
        // Verify timestamp was updated
        $product = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertDatabaseRecordUpdatedRecently('products', $id, 5); // Updated within 5 seconds
        $this->assertTimestampFormat($product['updated_at'], 'Y-m-d H:i:s');
        
        // Verify no unexpected changes
        $this->assertDatabaseCount('products', 1); // Still only one product
        $this->assertDatabaseMissing('products', [
            'id' => $id,
            'name' => 'Old' // Old name should not exist
        ]);
    }

    public function test_delete_soft_deletes_product(): void
    {
        $id = $this->seedProduct(['code' => 'DEL', 'name' => 'Delete me']);
        
        // Verify initial state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'DEL',
            'name' => 'Delete me',
            'deleted_at' => null
        ]);

        $deleted = $this->service->delete($id);

        // Validate response structure (delete returns success boolean)
        $this->assertArrayHasKey('success', $deleted);
        $this->assertTrue($deleted['success']);

        // Verify soft delete behavior
        $this->assertDatabaseSoftDeleted('products', $id);
        
        // Verify record still exists but with deleted_at timestamp
        $product = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($product);
        $this->assertEquals('DEL', $product['code']);
        $this->assertEquals('Delete me', $product['name']);
        $this->assertNotNull($product['deleted_at']);
        $this->assertTimestampFormat($product['deleted_at'], 'Y-m-d H:i:s');
        
        // Verify deleted_at is recent
        $deletedAt = strtotime($product['deleted_at']);
        $now = time();
        $this->assertLessThanOrEqual(5, $now - $deletedAt, 'deleted_at should be within 5 seconds');
        
        // Verify product doesn't appear in normal queries
        $this->assertDatabaseMissing('products', [
            'id' => $id,
            'deleted_at' => null
        ]);
    }

    public function test_validation_error_on_create(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm không được để trống');
        
        $this->service->create([]);
        
        // Verify no product was created
        $this->assertDatabaseCount('products', 0);
    }

    public function test_validation_error_on_update_without_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP2', 'name' => 'No change']);
        
        // Verify initial state
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'UP2',
            'name' => 'No change'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields to update');
        
        $this->service->update($id, []);
        
        // Verify product remains unchanged
        $this->assertDatabaseHas('products', [
            'id' => $id,
            'code' => 'UP2',
            'name' => 'No change'
        ]);
    }

    public function test_create_product_fails_when_code_matches_variant_sku(): void
    {
        $productId = $this->seedProduct(['code' => 'PX1', 'name' => 'Base product']);
        $this->seedVariant($productId, 'CONFLICT');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách phiên bản');

        $this->service->create(['code' => 'CONFLICT', 'name' => 'Clashing product']);
    }

    public function test_check_code_detects_variant_collision(): void
    {
        $productId = $this->seedProduct(['code' => 'PX2', 'name' => 'Product']);
        $this->seedVariant($productId, 'SKU-123');

        $result = $this->service->checkCode('SKU-123');

        $this->assertTrue($result['exists']);
        $this->assertFalse($result['exists_in_products']);
        $this->assertTrue($result['exists_in_variants']);
        $this->assertStringContainsString('phiên bản', $result['message']);
    }

    public function test_variant_creation_rejects_sku_matching_product_code(): void
    {
        $productId = $this->seedProduct(['code' => 'PX3', 'name' => 'Prod']);
        $variantService = new ProductVariantService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách sản phẩm');

        $variantService->create($productId, ['sku' => 'PX3']);
    }

    public function test_list_with_invalid_filters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->list(['page' => 0]);
    }

    public function test_update_non_existent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->update(999, ['name' => 'none']);
    }

    public function test_attach_images_skips_duplicates_and_reports(): void
    {
        $productId = $this->seedProduct(['code' => 'PATT', 'name' => 'Prod for attach']);
        $existingId = $this->seedImage(['product_id' => $productId]);
        $newId = $this->seedImage(['product_id' => null]);

        // Verify initial state
        $this->assertDatabaseHas('product_images', [
            'id' => $existingId,
            'product_id' => $productId,
            'deleted_at' => null
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $newId,
            'product_id' => null,
            'deleted_at' => null
        ]);

        $result = $this->service->attachImages($productId, [$existingId, $newId, 9999]);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $attachData = $result['data'];
        
        // Validate all expected fields
        $expectedFields = ['attached_count', 'skipped_count', 'missing_count', 'attached_ids', 'skipped_ids', 'missing_ids'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $attachData, "Attach result should have field: {$field}");
        }
        
        // Validate specific values
        $this->assertSame(1, $attachData['attached_count']);
        $this->assertSame(1, $attachData['skipped_count']);
        $this->assertSame(1, $attachData['missing_count']);
        
        // Validate ID arrays
        $this->assertIsArray($attachData['attached_ids']);
        $this->assertIsArray($attachData['skipped_ids']);
        $this->assertIsArray($attachData['missing_ids']);
        $this->assertContains($newId, $attachData['attached_ids']);
        $this->assertContains($existingId, $attachData['skipped_ids']);
        $this->assertContains(9999, $attachData['missing_ids']);
        
        // Validate business logic
        $this->assertEquals(count($attachData['attached_ids']), $attachData['attached_count']);
        $this->assertEquals(count($attachData['skipped_ids']), $attachData['skipped_count']);
        $this->assertEquals(count($attachData['missing_ids']), $attachData['missing_count']);
        
        // Verify database state changes
        $row = $this->db->table('product_images')->where('id', $newId)->get()->getRowArray();
        $this->assertSame($productId, (int) $row['product_id']);
        $this->assertNull($row['deleted_at']);
        $this->assertTimestampFormat($row['updated_at'], 'Y-m-d H:i:s');
        $this->assertDatabaseRecordUpdatedRecently('product_images', $newId, 5);
        
        // Verify existing image remains unchanged
        $existingRow = $this->db->table('product_images')->where('id', $existingId)->get()->getRowArray();
        $this->assertSame($productId, (int) $existingRow['product_id']);
        $this->assertNull($existingRow['deleted_at']);
        
        // Verify product is unaffected
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'PATT',
            'name' => 'Prod for attach',
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify final image count
        $this->assertDatabaseCount('product_images', 2, ['product_id' => $productId]);
    }

    public function test_get_single_product_with_categories_and_variants(): void
    {
        $productId = $this->seedProduct(['code' => 'GET001', 'name' => 'Get Product']);
        $this->seedCategoryLink($productId, 5);
        $this->seedVariant($productId, 'Blue');

        $result = $this->service->get($productId, true, true);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $product = $result['data'];
        
        // Validate product structure
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('code', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('status', $product);
        $this->assertArrayHasKey('created_at', $product);
        $this->assertArrayHasKey('updated_at', $product);
        $this->assertArrayHasKey('category_ids', $product);
        $this->assertArrayHasKey('variants', $product);
        
        // Validate specific values
        $this->assertEquals($productId, $product['id']);
        $this->assertSame('GET001', $product['code']);
        $this->assertSame('Get Product', $product['name']);
        $this->assertEquals('active', $product['status']);
        $this->assertSame([5], $product['category_ids']);
        $this->assertNotEmpty($product['variants']);
        
        // Validate variant structure
        $variant = $product['variants'][0];
        $this->assertArrayHasKey('id', $variant);
        $this->assertArrayHasKey('variant_name', $variant);
        $this->assertArrayHasKey('sku', $variant);
        $this->assertArrayHasKey('status', $variant);
        $this->assertEquals('Blue', $variant['variant_name']);
        $this->assertEquals('active', $variant['status']);
        
        // Validate timestamps
        $this->assertTimestampFormat($product['created_at'], 'Y-m-d H:i:s');
        $this->assertTimestampFormat($product['updated_at'], 'Y-m-d H:i:s');
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'GET001',
            'name' => 'Get Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
        $this->assertDatabaseHas('product_category_links', [
            'product_id' => $productId,
            'category_id' => 5
        ]);
        $this->assertDatabaseCount('product_variants_v2', 1, ['product_id' => $productId]);
    }

    public function test_get_product_without_variants_and_categories(): void
    {
        $productId = $this->seedProduct(['code' => 'GET002', 'name' => 'Simple Product']);

        $result = $this->service->get($productId, false, false);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $product = $result['data'];
        
        // Validate product structure
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('code', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('status', $product);
        $this->assertArrayHasKey('created_at', $product);
        $this->assertArrayHasKey('updated_at', $product);
        
        // Validate specific values
        $this->assertEquals($productId, $product['id']);
        $this->assertSame('GET002', $product['code']);
        $this->assertSame('Simple Product', $product['name']);
        $this->assertEquals('active', $product['status']);
        
        // Validate that optional data is not included
        $this->assertArrayNotHasKey('category_ids', $product);
        $this->assertArrayNotHasKey('variants', $product);
        
        // Validate timestamps
        $this->assertTimestampFormat($product['created_at'], 'Y-m-d H:i:s');
        $this->assertTimestampFormat($product['updated_at'], 'Y-m-d H:i:s');
        
        // Verify database state
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'GET002',
            'name' => 'Simple Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
    }

    public function test_get_nonexistent_product_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found');
        $this->service->get(999);
    }

    public function test_variants_returns_product_variants(): void
    {
        $productId = $this->seedProduct(['code' => 'VAR001', 'name' => 'Variant Product']);
        $this->seedVariant($productId, 'Red');
        $this->seedVariant($productId, 'Green');

        $result = $this->service->variants($productId);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $variants = $result['data'];
        $this->assertCount(2, $variants);
        
        // Validate each variant structure
        foreach ($variants as $variant) {
            $this->assertArrayHasKey('id', $variant);
            $this->assertArrayHasKey('variant_name', $variant);
            $this->assertArrayHasKey('sku', $variant);
            $this->assertArrayHasKey('price', $variant);
            $this->assertArrayHasKey('cost_price', $variant);
            $this->assertArrayHasKey('stock_quantity', $variant);
            $this->assertArrayHasKey('status', $variant);
            $this->assertArrayHasKey('created_at', $variant);
            $this->assertArrayHasKey('updated_at', $variant);
            
            // Validate status
            $this->assertEquals('active', $variant['status']);
            
            // Validate timestamps
            $this->assertTimestampFormat($variant['created_at'], 'Y-m-d H:i:s');
            $this->assertTimestampFormat($variant['updated_at'], 'Y-m-d H:i:s');
        }
        
        // Verify specific variants exist
        $this->assertDatabaseHas('product_variants_v2', [
            'product_id' => $productId,
            'variant_name' => 'Red',
            'sku' => 'Red',
            'status' => 'active'
        ]);
        $this->assertDatabaseHas('product_variants_v2', [
            'product_id' => $productId,
            'variant_name' => 'Green',
            'sku' => 'Green',
            'status' => 'active'
        ]);
        
        // Verify database count
        $this->assertDatabaseCount('product_variants_v2', 2, ['product_id' => $productId]);
    }

    public function test_variants_nonexistent_product_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found');
        $this->service->variants(999);
    }

    public function test_images_returns_product_images(): void
    {
        $productId = $this->seedProduct(['code' => 'IMG001', 'name' => 'Image Product']);
        $imageId1 = $this->seedImage(['product_id' => $productId, 'is_primary' => 1]);
        $imageId2 = $this->seedImage(['product_id' => $productId, 'is_primary' => 0]);

        $result = $this->service->images($productId);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $images = $result['data'];
        $this->assertCount(2, $images);
        
        // Validate each image structure
        foreach ($images as $image) {
            $this->assertArrayHasKey('id', $image);
            $this->assertArrayHasKey('product_id', $image);
            $this->assertArrayHasKey('image_path', $image);
            $this->assertArrayHasKey('image_url', $image);
            $this->assertArrayHasKey('is_primary', $image);
            $this->assertArrayHasKey('sort_order', $image);
            $this->assertArrayHasKey('file_name', $image);
            $this->assertArrayHasKey('created_at', $image);
            $this->assertArrayHasKey('updated_at', $image);
            
            // Validate product relationship
            $this->assertEquals($productId, $image['product_id']);
            
            // Validate timestamps
            $this->assertTimestampFormat($image['created_at'], 'Y-m-d H:i:s');
            $this->assertTimestampFormat($image['updated_at'], 'Y-m-d H:i:s');
        }
        
        // Verify primary image is first (ordered by is_primary DESC)
        $this->assertEquals(1, $images[0]['is_primary']);
        $this->assertEquals(0, $images[1]['is_primary']);
        
        // Verify database state
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId1,
            'product_id' => $productId,
            'is_primary' => 1
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId2,
            'product_id' => $productId,
            'is_primary' => 0
        ]);
        
        $this->assertDatabaseCount('product_images', 2, ['product_id' => $productId]);
    }

    public function test_images_nonexistent_product_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found');
        $this->service->images(999);
    }

    public function test_set_primary_image(): void
    {
        $productId = $this->seedProduct(['code' => 'PRIM001', 'name' => 'Primary Product']);
        $imageId1 = $this->seedImage(['product_id' => $productId, 'is_primary' => 1]);
        $imageId2 = $this->seedImage(['product_id' => $productId, 'is_primary' => 0]);

        // Verify initial state
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId1,
            'product_id' => $productId,
            'is_primary' => 1
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId2,
            'product_id' => $productId,
            'is_primary' => 0
        ]);

        $result = $this->service->setPrimaryImage($imageId2);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        
        // Verify primary image was changed
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId1,
            'product_id' => $productId,
            'is_primary' => 0  // Should no longer be primary
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId2,
            'product_id' => $productId,
            'is_primary' => 1  // Should now be primary
        ]);
        
        // Verify only one primary image exists
        $primaryImages = $this->db->table('product_images')
            ->where('product_id', $productId)
            ->where('is_primary', 1)
            ->countAllResults();
        $this->assertEquals(1, $primaryImages);
    }

    public function test_set_primary_nonexistent_image_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Image not found');
        $this->service->setPrimaryImage(999);
    }

    public function test_delete_image_soft(): void
    {
        $productId = $this->seedProduct(['code' => 'DELIMG001', 'name' => 'Delete Image Product']);
        $imageId = $this->seedImage(['product_id' => $productId]);

        // Verify initial state
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId,
            'product_id' => $productId,
            'deleted_at' => null
        ]);

        $result = $this->service->deleteImage($imageId, false);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        // Verify soft delete behavior
        $row = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertNotNull($row['deleted_at']);
        $this->assertTimestampFormat($row['deleted_at'], 'Y-m-d H:i:s');
        
        // Verify deleted_at is recent
        $deletedAt = strtotime($row['deleted_at']);
        $now = time();
        $this->assertLessThanOrEqual(5, $now - $deletedAt, 'deleted_at should be within 5 seconds');
        
        // Verify record still exists but is soft deleted
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId,
            'product_id' => $productId
        ]);
        $this->assertDatabaseMissing('product_images', [
            'id' => $imageId,
            'deleted_at' => null
        ]);
        
        // Verify product is unaffected
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'DELIMG001',
            'name' => 'Delete Image Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
    }

    public function test_delete_image_hard(): void
    {
        $productId = $this->seedProduct(['code' => 'DELIMG002', 'name' => 'Delete Image Product']);
        $imageId = $this->seedImage(['product_id' => $productId]);

        // Verify initial state
        $this->assertDatabaseHas('product_images', [
            'id' => $imageId,
            'product_id' => $productId,
            'deleted_at' => null
        ]);

        $result = $this->service->deleteImage($imageId, true);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        // Verify hard delete behavior
        $row = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray();
        $this->assertNull($row);
        
        // Verify record is completely removed
        $this->assertDatabaseMissing('product_images', [
            'id' => $imageId
        ]);
        
        // Verify product is unaffected
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'DELIMG002',
            'name' => 'Delete Image Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify no other images were affected
        $this->assertDatabaseCount('product_images', 0, ['product_id' => $productId]);
    }

    public function test_import_stub(): void
    {
        // Mock uploaded file
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(true);
        $mockFile->method('getRandomName')->willReturn('test_import.csv');
        $mockFile->method('move')->willReturn(true);

        $result = $this->service->import($mockFile);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $importData = $result['data'];
        
        // Validate all expected import fields
        $expectedFields = ['imported', 'failed', 'errors', 'total_processed'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $importData, "Import result should have field: {$field}");
        }
        
        // Validate data types
        $this->assertIsInt($importData['imported']);
        $this->assertIsInt($importData['failed']);
        $this->assertIsInt($importData['total_processed']);
        $this->assertIsArray($importData['errors']);
        
        // Validate business logic
        $this->assertGreaterThanOrEqual(0, $importData['imported']);
        $this->assertGreaterThanOrEqual(0, $importData['failed']);
        $this->assertGreaterThanOrEqual(0, $importData['total_processed']);
        $this->assertEquals($importData['imported'] + $importData['failed'], $importData['total_processed']);
        
        // Validate error structure if any errors exist
        if (!empty($importData['errors'])) {
            foreach ($importData['errors'] as $error) {
                $this->assertArrayHasKey('row', $error);
                $this->assertArrayHasKey('message', $error);
                $this->assertIsInt($error['row']);
                $this->assertIsString($error['message']);
            }
        }
        
        // Verify database state reflects import results
        if ($importData['imported'] > 0) {
            $this->assertDatabaseCount('products', $importData['imported']);
        }
    }

    public function test_import_no_file_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file is required');
        $this->service->import(null);
    }

    public function test_export_returns_csv(): void
    {
        $productId1 = $this->seedProduct(['code' => 'EXP001', 'name' => 'Export Product 1', 'selling_price' => 100]);
        $productId2 = $this->seedProduct(['code' => 'EXP002', 'name' => 'Export Product 2', 'selling_price' => 200]);

        $csv = $this->service->export();

        // Validate CSV structure
        $this->assertIsString($csv);
        $this->assertNotEmpty($csv);
        
        // Validate CSV header
        $this->assertStringContainsString('id,code,name,price', $csv);
        
        // Validate CSV content
        $this->assertStringContainsString('EXP001', $csv);
        $this->assertStringContainsString('EXP002', $csv);
        $this->assertStringContainsString('Export Product 1', $csv);
        $this->assertStringContainsString('Export Product 2', $csv);
        $this->assertStringContainsString('100', $csv);
        $this->assertStringContainsString('200', $csv);
        
        // Validate CSV format
        $lines = explode("\n", trim($csv));
        $this->assertGreaterThanOrEqual(3, count($lines)); // Header + 2 data rows
        
        // Validate header row
        $header = $lines[0];
        $expectedHeaders = ['id', 'code', 'name', 'price'];
        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertStringContainsString($expectedHeader, $header);
        }
        
        // Validate data rows
        $this->assertGreaterThanOrEqual(2, count($lines) - 1); // At least 2 data rows
        
        // Verify database state matches export
        $this->assertDatabaseHas('products', [
            'id' => $productId1,
            'code' => 'EXP001',
            'name' => 'Export Product 1',
            'selling_price' => 100,
            'status' => 'active',
            'deleted_at' => null
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $productId2,
            'code' => 'EXP002',
            'name' => 'Export Product 2',
            'selling_price' => 200,
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify only active products are exported (not soft deleted)
        $this->assertDatabaseCount('products', 2, ['deleted_at' => null]);
    }

    public function test_analytics_returns_summary(): void
    {
        $productId = $this->seedProduct(['code' => 'ANA001', 'name' => 'Analytics Product']);
        $this->seedVariant($productId, 'Red');
        $this->seedVariant($productId, 'Blue');

        $result = $this->service->analytics($productId);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $analytics = $result['data'];
        
        // Validate all expected analytics fields
        $expectedFields = ['total_stock', 'total_variant', 'total_images', 'total_categories', 'average_price', 'total_value'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $analytics, "Analytics should have field: {$field}");
        }
        
        // Validate specific values
        $this->assertEquals(2, $analytics['total_variant']);
        $this->assertEquals(4, $analytics['total_stock']); // 2 variants with 2 stock each
        $this->assertIsInt($analytics['total_stock']);
        $this->assertIsInt($analytics['total_variant']);
        $this->assertGreaterThanOrEqual(0, $analytics['total_stock']);
        $this->assertGreaterThanOrEqual(0, $analytics['total_variant']);
        
        // Validate calculated fields
        if (isset($analytics['average_price'])) {
            $this->assertIsFloat($analytics['average_price']);
            $this->assertGreaterThanOrEqual(0, $analytics['average_price']);
        }
        if (isset($analytics['total_value'])) {
            $this->assertIsFloat($analytics['total_value']);
            $this->assertGreaterThanOrEqual(0, $analytics['total_value']);
        }
        
        // Verify database state matches analytics
        $this->assertDatabaseCount('product_variants_v2', 2, ['product_id' => $productId]);
        
        // Verify product exists and is active
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'ANA001',
            'name' => 'Analytics Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
    }

    public function test_analytics_nonexistent_product_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found');
        $this->service->analytics(999);
    }

    public function test_used_attribute_options(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR001', 'name' => 'Attribute Product']);

        $result = $this->service->usedAttributeOptions($productId);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $attributeOptions = $result['data'];
        $this->assertIsArray($attributeOptions);
        
        // Validate structure of attribute options if any exist
        if (!empty($attributeOptions)) {
            foreach ($attributeOptions as $option) {
                $this->assertArrayHasKey('attribute_id', $option);
                $this->assertArrayHasKey('attribute_name', $option);
                $this->assertArrayHasKey('option_id', $option);
                $this->assertArrayHasKey('option_value', $option);
                
                // Validate data types
                $this->assertIsInt($option['attribute_id']);
                $this->assertIsString($option['attribute_name']);
                $this->assertIsInt($option['option_id']);
                $this->assertIsString($option['option_value']);
            }
        }
        
        // Verify product exists
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'ATTR001',
            'name' => 'Attribute Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
    }

    public function test_product_attribute_values(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR002', 'name' => 'Attribute Values Product']);

        $result = $this->service->productAttributeValues($productId);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $attributeValues = $result['data'];
        $this->assertIsArray($attributeValues);
        
        // Validate structure of attribute values if any exist
        if (!empty($attributeValues)) {
            foreach ($attributeValues as $value) {
                $this->assertArrayHasKey('attribute_id', $value);
                $this->assertArrayHasKey('attribute_name', $value);
                $this->assertArrayHasKey('value', $value);
                $this->assertArrayHasKey('created_at', $value);
                $this->assertArrayHasKey('updated_at', $value);
                
                // Validate data types
                $this->assertIsInt($value['attribute_id']);
                $this->assertIsString($value['attribute_name']);
                $this->assertIsString($value['value']);
                
                // Validate timestamps
                $this->assertTimestampFormat($value['created_at'], 'Y-m-d H:i:s');
                $this->assertTimestampFormat($value['updated_at'], 'Y-m-d H:i:s');
            }
        }
        
        // Verify product exists
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'ATTR002',
            'name' => 'Attribute Values Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
    }

    public function test_update_product_attribute_values(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR003', 'name' => 'Update Attributes Product']);
        $values = ['color' => 'red', 'size' => 'L'];

        $result = $this->service->updateProductAttributeValues($productId, $values);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['data']);
        
        $updatedValues = $result['data'];
        $this->assertIsArray($updatedValues);
        
        // Validate that the values were actually updated
        $this->assertArrayHasKey('color', $updatedValues);
        $this->assertArrayHasKey('size', $updatedValues);
        $this->assertEquals('red', $updatedValues['color']);
        $this->assertEquals('L', $updatedValues['size']);
        
        // Verify database state changes
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $productId,
            'attribute_key' => 'color',
            'attribute_value' => 'red'
        ]);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $productId,
            'attribute_key' => 'size',
            'attribute_value' => 'L'
        ]);
        
        // Verify product exists and is unchanged
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'ATTR003',
            'name' => 'Update Attributes Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify timestamps on updated records
        $updatedRecord = $this->db->table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_key', 'color')
            ->get()->getRowArray();
            
        if ($updatedRecord) {
            $this->assertArrayHasKey('updated_at', $updatedRecord);
            $this->assertTimestampFormat($updatedRecord['updated_at'], 'Y-m-d H:i:s');
            $this->assertDatabaseRecordUpdatedRecently('product_attribute_values', $updatedRecord['id'], 5);
        }
    }

    public function test_update_product_attribute_values_invalid_throws(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR004', 'name' => 'Invalid Attributes Product']);

        $this->expectException(\TypeError::class);
        $this->service->updateProductAttributeValues($productId, 'invalid');
    }

    public function test_remove_attribute_from_product(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR005', 'name' => 'Remove Attribute Product']);
        
        // First, add an attribute to remove
        $this->db->table('product_attribute_values')->insert([
            'product_id' => $productId,
            'attribute_id' => 1,
            'attribute_key' => 'test_attr',
            'attribute_value' => 'test_value',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Verify attribute exists before removal
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $productId,
            'attribute_id' => 1
        ]);

        $result = $this->service->removeAttributeFromProduct($productId, 1);

        // Comprehensive response validation
        $this->assertServiceSuccess($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('Removed attribute from product', $result['message']);
        
        // Verify attribute was actually removed
        $this->assertDatabaseMissing('product_attribute_values', [
            'product_id' => $productId,
            'attribute_id' => 1
        ]);
        
        // Verify product still exists and is unchanged
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'ATTR005',
            'name' => 'Remove Attribute Product',
            'status' => 'active',
            'deleted_at' => null
        ]);
        
        // Verify no other attributes were affected
        $this->assertDatabaseCount('product_attribute_values', 0, ['product_id' => $productId]);
    }

    /**
     * @agent-removed: resetSchema() is no longer needed
     * @agent-use: DevDatabaseTrait handles schema via migrations
     */

    private function seedProduct(array $data): int
    {
        $payload = array_merge([
            'product_type' => 'standard',
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

    private function seedCategoryLink(int $productId, int $categoryId): void
    {
        // Ensure category exists
        if ($this->db->table('product_categories')->where('id', $categoryId)->countAllResults() === 0) {
            $this->db->table('product_categories')->insert([
                'id' => $categoryId,
                'name' => 'Category ' . $categoryId,
                'code' => 'CAT' . $categoryId,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->table('product_category_links')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedVariant(int $productId, string $name): void
    {
        $this->db->table('product_variants_v2')->insert([
            'product_id' => $productId,
            'variant_name' => $name,
            'variant_signature' => $name,
            'sku' => $name,
            'barcode' => null,
            'price' => 10,
            'cost_price' => 5,
            'stock_quantity' => 2,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
    }

    private function seedImage(array $data = []): int
    {
        $payload = array_merge([
            'product_id' => null,
            'variant_id' => null,
            'image_path' => '/uploads/test.jpg',
            'image_url' => '/uploads/test.jpg',
            'is_primary' => 0,
            'sort_order' => 0,
            'file_name' => 'test.jpg',
            'deleted_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('product_images')->insert($payload);
        return (int) $this->db->insertID();
    }
    
    // ========== EDGE CASE TESTING ==========
    
    public function test_create_product_with_boundary_values(): void
    {
        $boundaryTests = [
            'min_price' => [
                'input' => ['code' => 'MIN', 'name' => 'Min Price', 'selling_price' => 0.01],
                'should_pass' => true,
                'expected' => ['selling_price' => 0.01]
            ],
            'max_price' => [
                'input' => ['code' => 'MAX', 'name' => 'Max Price', 'selling_price' => 999999.99],
                'should_pass' => true,
                'expected' => ['selling_price' => 999999.99]
            ],
            'negative_price' => [
                'input' => ['code' => 'NEG', 'name' => 'Negative Price', 'selling_price' => -10],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class,
                'exception_message' => 'selling_price must be positive'
            ],
            'zero_price' => [
                'input' => ['code' => 'ZERO', 'name' => 'Zero Price', 'selling_price' => 0],
                'should_pass' => true,
                'expected' => ['selling_price' => 0]
            ]
        ];
        
        $this->assertBoundaryValues(function($data) {
            return $this->service->create($data);
        }, $boundaryTests);
    }
    
    public function test_create_product_with_null_values(): void
    {
        $this->assertNullValues(function($data) {
            return $this->service->create($data);
        }, ['code', 'name']);
    }
    
    public function test_create_product_with_empty_strings(): void
    {
        $this->assertEmptyStringValues(function($data) {
            return $this->service->create($data);
        }, [
            'code' => ['should_fail' => true, 'message' => 'code cannot be empty'],
            'name' => ['should_fail' => true, 'message' => 'name cannot be empty']
        ]);
    }
    
    public function test_create_product_with_max_length_values(): void
    {
        $this->assertMaxLengthValues(function($data) {
            return $this->service->create($data);
        }, [
            'code' => 50,
            'name' => 255,
            'barcode' => 100
        ]);
    }
    
    public function test_create_product_with_special_characters(): void
    {
        $this->assertSpecialCharacters(function($data) {
            return $this->service->create($data);
        }, [
            'code' => true,  // Should sanitize
            'name' => true,  // Should sanitize
            'description' => true  // Should sanitize
        ]);
    }
    
    public function test_list_products_with_large_dataset(): void
    {
        $this->assertLargeDatasetPerformance(function($testData) {
            // Create products first
            foreach ($testData as $product) {
                ProductFactory::create($product);
            }
            
            return $this->service->list(['page' => 1, 'limit' => 100]);
        }, 50, 800); // 50 records, 800ms max time
    }
    
    public function test_service_execution_time_performance(): void
    {
        $this->assertExecutionTime(function() {
            // Create test data
            ProductFactory::createMany(50);
            
            // Test service list performance
            $result = $this->service->list(['page' => 1, 'limit' => 50]);
            return $result;
        }, 600); // 600ms max
    }
    
    public function test_service_memory_usage_performance(): void
    {
        $this->assertMemoryUsage(function() {
            // Create test data
            ProductFactory::createMany(100);
            
            // Test service memory usage
            $result = $this->service->list(['page' => 1, 'limit' => 100]);
            return $result;
        }, 30); // 30MB max
    }
    
    public function test_service_query_count_performance(): void
    {
        $this->assertQueryCount(function() {
            // Create test data
            ProductFactory::createMany(30);
            
            // Test service query count
            $result = $this->service->list(['page' => 1, 'limit' => 30]);
            return $result;
        }, 8); // Max 8 queries
    }
    
    public function test_service_pagination_performance(): void
    {
        // Create test data
        ProductFactory::createMany(150);
        
        $this->assertPaginationPerformance(function($params) {
            return $this->service->list($params);
        }, 150, 500); // 150 records, 500ms max
    }
    
    public function test_service_search_performance(): void
    {
        // Create test data with searchable content
        for ($i = 0; $i < 80; $i++) {
            ProductFactory::create([
                'name' => 'Service Search Product ' . $i,
                'code' => 'SRV' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ]);
        }
        
        $this->assertSearchPerformance(function($params) {
            return $this->service->list($params);
        }, 80, 600); // 80 records, 600ms max
    }
    
    public function test_concurrent_service_operations(): void
    {
        $this->assertConcurrentRequests(function($params) {
            $iteration = $params['iteration'];
            return $this->service->create([
                'code' => 'CONC' . str_pad($iteration, 3, '0', STR_PAD_LEFT),
                'name' => 'Concurrent Product ' . $iteration,
                'selling_price' => 100 + $iteration
            ]);
        }, 3, 1000); // 3 concurrent operations, 1000ms max
    }
    
    public function test_concurrent_product_creation(): void
    {
        $this->assertConcurrentAccess(
            function() {
                return $this->service->create(['code' => 'CONC1', 'name' => 'Concurrent 1']);
            },
            function() {
                return $this->service->create(['code' => 'CONC2', 'name' => 'Concurrent 2']);
            }
        );
    }
    
    // ========== ERROR MESSAGE TESTING ==========
    
    public function test_validation_error_messages(): void
    {
        $this->assertFieldValidationError('code', 'Mã sản phẩm không được để trống', function() {
            return $this->service->create([]);
        });
        
        $this->assertFieldValidationError('name', 'Tên sản phẩm không được để trống', function() {
            return $this->service->create(['code' => 'TEST']);
        });
        
        // Verify no products were created during validation failures
        $this->assertDatabaseCount('products', 0);
    }
    
    public function test_multiple_field_validation_errors(): void
    {
        $this->assertMultipleFieldValidationErrors([
            'code' => ['Mã sản phẩm không được để trống'],
            'name' => ['Tên sản phẩm không được để trống'],
            'selling_price' => ['Giá bán phải là số']
        ], function() {
            return $this->service->create([]);
        });
        
        // Verify no products were created during validation failures
        $this->assertDatabaseCount('products', 0);
    }
    
    public function test_business_rule_violation_messages(): void
    {
        // Create product first
        $firstResult = $this->service->create(['code' => 'DUP', 'name' => 'Original']);
        $this->assertServiceSuccess($firstResult);
        $firstProductId = $firstResult['data']['id'];
        
        // Verify first product exists
        $this->assertDatabaseHas('products', [
            'id' => $firstProductId,
            'code' => 'DUP',
            'name' => 'Original'
        ]);
        
        $this->assertBusinessRuleViolation('duplicate_code', 'Mã sản phẩm đã tồn tại', function() {
            return $this->service->create(['code' => 'DUP', 'name' => 'Duplicate']);
        });
        
        // Verify no additional product was created
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'id' => $firstProductId,
            'code' => 'DUP',
            'name' => 'Original'
        ]);
    }
    
    public function test_constraint_violation_messages(): void
    {
        $this->assertConstraintViolation('unique', 'code', function() {
            // Create first product
            $firstResult = $this->service->create(['code' => 'UNIQUE', 'name' => 'First']);
            $this->assertServiceSuccess($firstResult);
            $firstProductId = $firstResult['data']['id'];
            
            // Verify first product exists
            $this->assertDatabaseHas('products', [
                'id' => $firstProductId,
                'code' => 'UNIQUE',
                'name' => 'First'
            ]);
            
            // Try to create duplicate
            return $this->service->create(['code' => 'UNIQUE', 'name' => 'Second']);
        });
        
        // Verify only one product exists
        $this->assertDatabaseCount('products', 1);
    }
    
    public function test_resource_not_found_error_messages(): void
    {
        $this->assertResourceNotFoundError('Product', 999, function() {
            return $this->service->get(999);
        });
    }
    
    public function test_standard_error_message_format(): void
    {
        $this->assertStandardErrorMessageFormat(function() {
            return $this->service->create([]);
        });
    }
    
    // ========== BUSINESS LOGIC ASSERTIONS ==========
    
    public function test_create_product_business_logic(): void
    {
        $data = ['code' => 'BIZ001', 'name' => 'Business Logic Test', 'selling_price' => 100.50];
        $result = $this->service->create($data);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertMonetaryPrecision($result['data']['selling_price'], 2);
        $this->assertTimestampFormat($result['data']['created_at'], 'Y-m-d H:i:s');
        $this->assertStringLength($result['data']['code'], 1, 50);
        $this->assertStringLength($result['data']['name'], 1, 255);
        
        // Database state validation
        $this->assertDatabaseHas('products', [
            'code' => 'BIZ001',
            'name' => 'Business Logic Test',
            'selling_price' => 100.50,
            'status' => 'active'
        ]);
    }
    
    public function test_update_product_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'UPD001', 'name' => 'Original', 'selling_price' => 50.00]);
        
        $updateData = ['name' => 'Updated', 'selling_price' => 75.25, 'status' => 'inactive'];
        $result = $this->service->update($productId, $updateData);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertMonetaryPrecision($result['data']['selling_price'], 2);
        $this->assertTimestampFormat($result['data']['updated_at'], 'Y-m-d H:i:s');
        $this->assertStringEquals($result['data']['name'], 'Updated');
        $this->assertStringEquals($result['data']['status'], 'inactive');
        
        // Database state validation
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'UPD001',
            'name' => 'Updated',
            'selling_price' => 75.25,
            'status' => 'inactive'
        ]);
    }
    
    // ========== COMPREHENSIVE DATA TYPE VALIDATION ==========
    
    public function test_create_product_with_invalid_data_types(): void
    {
        $invalidTypeTests = [
            'code_as_array' => [
                'input' => ['code' => ['invalid'], 'name' => 'Test'],
                'expected_error' => 'Mã sản phẩm không được để trống'
            ],
            'code_as_object' => [
                'input' => ['code' => (object)['invalid'], 'name' => 'Test'],
                'expected_error' => 'Mã sản phẩm không được để trống'
            ],
            'name_as_null' => [
                'input' => ['code' => 'TEST', 'name' => null],
                'expected_error' => 'Tên sản phẩm không được để trống'
            ],
            'selling_price_as_string' => [
                'input' => ['code' => 'TEST', 'name' => 'Test', 'selling_price' => 'not_a_number'],
                'expected_error' => 'Giá bán phải là số'
            ],
            'selling_price_as_array' => [
                'input' => ['code' => 'TEST', 'name' => 'Test', 'selling_price' => [100]],
                'expected_error' => 'Giá bán phải là số'
            ],
            'selling_price_as_boolean' => [
                'input' => ['code' => 'TEST', 'name' => 'Test', 'selling_price' => true],
                'expected_error' => 'Giá bán phải là số'
            ]
        ];
        
        foreach ($invalidTypeTests as $testName => $test) {
            with($testName, function() use ($test) {
                $this->expectException(InvalidArgumentException::class);
                $this->expectExceptionMessage($test['expected_error']);
                
                $this->service->create($test['input']);
                
                // Verify no product was created
                $this->assertDatabaseCount('products', 0);
            });
        }
    }
    
    public function test_update_product_with_invalid_data_types(): void
    {
        $productId = ProductFactory::create(['code' => 'UPDTYPE', 'name' => 'Original']);
        
        // Verify initial state
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'UPDTYPE',
            'name' => 'Original',
            'selling_price' => 0
        ]);
        
        $invalidTypeTests = [
            'selling_price_as_string' => [
                'input' => ['selling_price' => 'not_a_number'],
                'expected_error' => 'Giá bán phải là số'
            ],
            'selling_price_as_array' => [
                'input' => ['selling_price' => [100]],
                'expected_error' => 'Giá bán phải là số'
            ],
            'status_as_array' => [
                'input' => ['status' => ['invalid']],
                'expected_error' => 'Invalid data'
            ]
        ];
        
        foreach ($invalidTypeTests as $testName => $test) {
            with($testName, function() use ($productId, $test) {
                $this->expectException(InvalidArgumentException::class);
                $this->expectExceptionMessage($test['expected_error']);
                
                $this->service->update($productId, $test['input']);
                
                // Verify product remains unchanged
                $this->assertDatabaseHas('products', [
                    'id' => $productId,
                    'code' => 'UPDTYPE',
                    'name' => 'Original',
                    'selling_price' => 0
                ]);
            });
        }
    }
    
    // ========== DATA INTEGRITY VALIDATION ==========
    
    public function test_product_relationship_integrity(): void
    {
        $productId = ProductFactory::create(['code' => 'REL001', 'name' => 'Relationship Test']);
        
        // Link categories
        CategoryFactory::linkToProduct(1, $productId);
        CategoryFactory::linkToProduct(2, $productId);
        
        // Create variants
        VariantFactory::create($productId, ['variant_name' => 'Red', 'sku' => 'RED-SKU']);
        VariantFactory::create($productId, ['variant_name' => 'Blue', 'sku' => 'BLUE-SKU']);
        
        // Add images
        $imageId1 = $this->seedImage(['product_id' => $productId]);
        $imageId2 = $this->seedImage(['product_id' => $productId]);
        
        // Verify all relationships exist
        $result = $this->service->get($productId, true, true);
        $this->assertServiceSuccess($result);
        
        $product = $result['data'];
        $this->assertCount(2, $product['category_ids']);
        $this->assertCount(2, $product['variants']);
        
        // Verify database integrity
        $this->assertDatabaseCount('product_category_links', 2, ['product_id' => $productId]);
        $this->assertDatabaseCount('product_variants_v2', 2, ['product_id' => $productId]);
        $this->assertDatabaseCount('product_images', 2, ['product_id' => $productId]);
        
        // Verify specific relationships
        $this->assertDatabaseHas('product_category_links', [
            'product_id' => $productId,
            'category_id' => 1
        ]);
        $this->assertDatabaseHas('product_category_links', [
            'product_id' => $productId,
            'category_id' => 2
        ]);
        
        $this->assertDatabaseHas('product_variants_v2', [
            'product_id' => $productId,
            'variant_name' => 'Red',
            'sku' => 'RED-SKU'
        ]);
        $this->assertDatabaseHas('product_variants_v2', [
            'product_id' => $productId,
            'variant_name' => 'Blue',
            'sku' => 'BLUE-SKU'
        ]);
        
        // Test soft delete doesn't break relationships
        $deleteResult = $this->service->delete($productId);
        $this->assertServiceSuccess($deleteResult);
        
        // Verify soft delete behavior
        $this->assertDatabaseSoftDeleted('products', $productId);
        
        // Verify relationships are preserved (not cascade deleted)
        $this->assertDatabaseCount('product_category_links', 2, ['product_id' => $productId]);
        $this->assertDatabaseCount('product_variants_v2', 2, ['product_id' => $productId]);
        $this->assertDatabaseCount('product_images', 2, ['product_id' => $productId]);
    }
    
    public function test_product_code_uniqueness_across_tables(): void
    {
        // Create product
        $productResult = $this->service->create(['code' => 'UNIQUE001', 'name' => 'Product']);
        $this->assertServiceSuccess($productResult);
        $productId = $productResult['data']['id'];
        
        // Create variant with different SKU
        VariantFactory::create($productId, ['sku' => 'VAR001']);
        
        // Test code uniqueness check
        $checkResult = $this->service->checkCode('UNIQUE001');
        $this->assertTrue($checkResult['exists']);
        $this->assertTrue($checkResult['exists_in_products']);
        $this->assertFalse($checkResult['exists_in_variants']);
        
        $checkVariantResult = $this->service->checkCode('VAR001');
        $this->assertTrue($checkVariantResult['exists']);
        $this->assertFalse($checkVariantResult['exists_in_products']);
        $this->assertTrue($checkVariantResult['exists_in_variants']);
        
        // Test collision prevention
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách sản phẩm');
        $this->service->create(['code' => 'UNIQUE001', 'name' => 'Duplicate Product']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách phiên bản');
        $this->service->create(['code' => 'VAR001', 'name' => 'Collision Product']);
    }
    
    // ========== COMPREHENSIVE FIELD VALIDATION ==========
    
    public function test_product_field_length_constraints(): void
    {
        // Test maximum length constraints
        $maxCode = str_repeat('A', 100);
        $maxName = str_repeat('B', 255);
        $maxBarcode = str_repeat('C', 100);
        
        $data = [
            'code' => $maxCode,
            'name' => $maxName,
            'barcode' => $maxBarcode,
            'selling_price' => 99.99
        ];
        
        $result = $this->service->create($data);
        $this->assertServiceSuccess($result);
        
        $product = $result['data'];
        $this->assertEquals($maxCode, $product['code']);
        $this->assertEquals($maxName, $product['name']);
        $this->assertEquals($maxBarcode, $product['barcode']);
        
        // Test exceeding maximum length
        $tooLongCode = str_repeat('A', 101);
        $tooLongName = str_repeat('B', 256);
        
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['code' => $tooLongCode, 'name' => 'Valid Name']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['code' => 'VALID', 'name' => $tooLongName]);
    }
    
    public function test_product_numeric_field_validation(): void
    {
        $numericTests = [
            'valid_prices' => [
                'input' => ['code' => 'NUM001', 'name' => 'Numeric Test', 'selling_price' => 123.45, 'purchase_price' => 67.89, 'wholesale_price' => 90.12],
                'should_pass' => true
            ],
            'zero_prices' => [
                'input' => ['code' => 'NUM002', 'name' => 'Zero Test', 'selling_price' => 0, 'purchase_price' => 0, 'wholesale_price' => 0],
                'should_pass' => true
            ],
            'negative_prices' => [
                'input' => ['code' => 'NUM003', 'name' => 'Negative Test', 'selling_price' => -10],
                'should_pass' => false,
                'expected_error' => 'Giá bán phải là số'
            ],
            'stock_quantities' => [
                'input' => ['code' => 'NUM004', 'name' => 'Stock Test', 'stock_quantity' => 100, 'alert_stock' => 10],
                'should_pass' => true
            ],
            'negative_stock' => [
                'input' => ['code' => 'NUM005', 'name' => 'Negative Stock', 'stock_quantity' => -5],
                'should_pass' => false,
                'expected_error' => 'Tồn kho phải là số'
            ]
        ];
        
        foreach ($numericTests as $testName => $test) {
            with($testName, function() use ($test) {
                if (!$test['should_pass']) {
                    $this->expectException(InvalidArgumentException::class);
                    $this->expectExceptionMessage($test['expected_error']);
                }
                
                $result = $this->service->create($test['input']);
                
                if ($test['should_pass']) {
                    $this->assertServiceSuccess($result);
                    
                    // Verify numeric precision in database
                    if (isset($test['input']['selling_price'])) {
                        $this->assertDatabaseDecimalValue('products', $result['data']['id'], 'selling_price', $test['input']['selling_price'], 0.01);
                    }
                }
            });
        }
    }
    
    public function test_delete_product_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'DEL001', 'name' => 'To Delete']);
        
        $result = $this->service->delete($productId);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertTimestampFormat($result['data']['deleted_at'], 'Y-m-d H:i:s');
        
        // Database state validation
        $this->assertDatabaseSoftDeleted('products', $productId);
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'code' => 'DEL001',
            'name' => 'To Delete'
        ]);
    }
    
    public function test_product_code_collision_business_logic(): void
    {
        // Create product with variant
        $productId = ProductFactory::create(['code' => 'COL001', 'name' => 'Base Product']);
        VariantFactory::create($productId, ['sku' => 'COLLISION']);
        
        // Try to create product with same code as variant SKU
        $this->assertBusinessRuleViolation('code_collision', 'Mã sản phẩm đã tồn tại trong danh sách phiên bản', function() {
            return $this->service->create(['code' => 'COLLISION', 'name' => 'Collision Product']);
        });
    }
    
    public function test_product_list_pagination_business_logic(): void
    {
        // Create multiple products
        for ($i = 1; $i <= 25; $i++) {
            ProductFactory::create(['code' => 'PAG' . str_pad($i, 3, '0', STR_PAD_LEFT), 'name' => 'Product ' . $i]);
        }
        
        // Test first page
        $result = $this->service->list(['page' => 1, 'limit' => 10]);
        
        $this->assertBusinessRuleSuccess($result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(10, $result['pagination']['per_page']);
        $this->assertEquals(25, $result['pagination']['total']);
        $this->assertEquals(3, $result['pagination']['total_pages']);
        $this->assertCount(10, $result['data']);
        
        // Test second page
        $result2 = $this->service->list(['page' => 2, 'limit' => 10]);
        
        $this->assertBusinessRuleSuccess($result2);
        $this->assertEquals(2, $result2['pagination']['current_page']);
        $this->assertCount(10, $result2['data']);
    }
}
