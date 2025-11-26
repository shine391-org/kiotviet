<?php

namespace Tests\Services;

use App\Services\Products\ProductService;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: ProductService unified MySQL testing
 * @agent-pattern: Standard service test with DevDatabaseTrait
 */
class ProductServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use \Tests\Support\Database\ProductSchemaTrait;
    
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        $this->service = new ProductService();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_list_returns_products_with_categories_and_variants(): void
    {
        $productId = $this->seedProduct(['code' => 'P001', 'name' => 'Product 1']);
        $this->seedCategoryLink($productId, 3);
        $this->seedVariant($productId, 'Red');

        $result = $this->service->list(['page' => 1, 'limit' => 5, 'include_variants' => true]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame([3], $result['data'][0]['category_ids']);
        $this->assertNotEmpty($result['data'][0]['variants']);
        $this->assertSame(1, $result['pagination']['total']);
    }

    public function test_create_persists_product(): void
    {
        $result = $this->service->create(['code' => 'P100', 'name' => 'New product']);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        $row = $this->db->table('products')->where('id', $result['data']['id'])->get()->getRowArray();
        $this->assertSame('P100', $row['code']);
    }

    public function test_create_duplicate_code_throws(): void
    {
        $this->service->create(['code' => 'DUP', 'name' => 'One']);
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['code' => 'DUP', 'name' => 'Two']);
    }

    public function test_update_changes_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP1', 'name' => 'Old']);

        $updated = $this->service->update($id, ['name' => 'Updated', 'status' => 'inactive']);

        $this->assertTrue($updated['success']);
        $row = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertSame('inactive', $row['status']);
    }

    public function test_delete_soft_deletes_product(): void
    {
        $id = $this->seedProduct(['code' => 'DEL', 'name' => 'Delete me']);

        $deleted = $this->service->delete($id);

        $this->assertTrue($deleted['success']);

        $row = $this->db->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_validation_error_on_create(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([]);
    }

    public function test_validation_error_on_update_without_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP2', 'name' => 'No change']);
        $this->expectException(InvalidArgumentException::class);
        $this->service->update($id, []);
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

        $result = $this->service->attachImages($productId, [$existingId, $newId, 9999]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['attached_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertSame(1, $result['missing_count']);
        $this->assertContains($newId, $result['attached_ids']);
        $this->assertContains($existingId, $result['skipped_ids']);
        $this->assertContains(9999, $result['missing_ids']);

        $row = $this->db->table('product_images')->where('id', $newId)->get()->getRowArray();
        $this->assertSame($productId, (int) $row['product_id']);
        $this->assertNull($row['deleted_at']);
    }

    public function test_get_single_product_with_categories_and_variants(): void
    {
        $productId = $this->seedProduct(['code' => 'GET001', 'name' => 'Get Product']);
        $this->seedCategoryLink($productId, 5);
        $this->seedVariant($productId, 'Blue');

        $result = $this->service->get($productId, true, true);

        $this->assertTrue($result['success']);
        $this->assertSame('GET001', $result['data']['code']);
        $this->assertSame([5], $result['data']['category_ids']);
        $this->assertNotEmpty($result['data']['variants']);
    }

    public function test_get_product_without_variants_and_categories(): void
    {
        $productId = $this->seedProduct(['code' => 'GET002', 'name' => 'Simple Product']);

        $result = $this->service->get($productId, false, false);

        $this->assertTrue($result['success']);
        $this->assertSame('GET002', $result['data']['code']);
        $this->assertArrayNotHasKey('category_ids', $result['data']);
        $this->assertArrayNotHasKey('variants', $result['data']);
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

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
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
        $imageId1 = $this->seedImage(['product_id' => $productId]);
        $imageId2 = $this->seedImage(['product_id' => $productId]);

        $result = $this->service->images($productId);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
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
        $imageId = $this->seedImage(['product_id' => $productId]);

        $result = $this->service->setPrimaryImage($imageId);

        $this->assertTrue($result['success']);
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

        $result = $this->service->deleteImage($imageId, false);

        $this->assertTrue($result['success']);
        $row = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_delete_image_hard(): void
    {
        $productId = $this->seedProduct(['code' => 'DELIMG002', 'name' => 'Delete Image Product']);
        $imageId = $this->seedImage(['product_id' => $productId]);

        $result = $this->service->deleteImage($imageId, true);

        $this->assertTrue($result['success']);
        $row = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function test_import_stub(): void
    {
        // Mock uploaded file
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(true);
        $mockFile->method('getRandomName')->willReturn('test_import.csv');
        $mockFile->method('move')->willReturn(true);

        $result = $this->service->import($mockFile);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('imported', $result['data']);
        $this->assertArrayHasKey('failed', $result['data']);
        $this->assertArrayHasKey('errors', $result['data']);
    }

    public function test_import_no_file_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file is required');
        $this->service->import(null);
    }

    public function test_export_returns_csv(): void
    {
        $this->seedProduct(['code' => 'EXP001', 'name' => 'Export Product 1', 'selling_price' => 100]);
        $this->seedProduct(['code' => 'EXP002', 'name' => 'Export Product 2', 'selling_price' => 200]);

        $csv = $this->service->export();

        $this->assertStringContainsString('id,code,name,price', $csv);
        $this->assertStringContainsString('EXP001', $csv);
        $this->assertStringContainsString('EXP002', $csv);
        $this->assertStringContainsString('100', $csv);
        $this->assertStringContainsString('200', $csv);
    }

    public function test_analytics_returns_summary(): void
    {
        $productId = $this->seedProduct(['code' => 'ANA001', 'name' => 'Analytics Product']);
        $this->seedVariant($productId, 'Red');
        $this->seedVariant($productId, 'Blue');

        $result = $this->service->analytics($productId);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('total_stock', $result['data']);
        $this->assertArrayHasKey('total_variant', $result['data']);
        $this->assertSame(2, $result['data']['total_variant']);
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

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function test_product_attribute_values(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR002', 'name' => 'Attribute Values Product']);

        $result = $this->service->productAttributeValues($productId);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function test_update_product_attribute_values(): void
    {
        $productId = $this->seedProduct(['code' => 'ATTR003', 'name' => 'Update Attributes Product']);
        $values = ['color' => 'red', 'size' => 'L'];

        $result = $this->service->updateProductAttributeValues($productId, $values);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
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

        $result = $this->service->removeAttributeFromProduct($productId, 1);

        $this->assertTrue($result['success']);
        $this->assertSame('Removed attribute from product', $result['message']);
    }

    /**
     * @agent-removed: resetSchema() is no longer needed
     * @agent-use: DevDatabaseTrait handles schema via migrations
     */

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

    private function seedCategoryLink(int $productId, int $categoryId): void
    {
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
}
