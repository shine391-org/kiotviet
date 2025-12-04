<?php

namespace Tests\Services;

use App\Repositories\Products\ProductRepository;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Factories\ProductFactory;
use Tests\Support\Factories\VariantFactory;
use Tests\Support\Factories\CategoryFactory;
use CodeIgniter\HTTP\Files\UploadedFile;

class ProductVariantServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use \Tests\Support\Database\ProductSchemaTrait;
    
    private ProductVariantService $service;
    protected ProductRepository $productRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forceFreshMigrate(); // Force fresh schema to include attributes tables
        $this->productRepo = new ProductRepository(null, null, null, $this->db);
        $this->service = new ProductVariantService(null, null, $this->productRepo);
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    // Factory methods replaced with proper factories
    // Use ProductFactory::create() and VariantFactory::create() instead

    public function test_show_returns_variant(): void
    {
        $pid = ProductFactory::create(['code' => 'P1']);
        $vid = VariantFactory::create($pid, ['sku' => 'SKU1']);

        $result = $this->service->show($vid);
        
        // Strong assertions with database validation
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($vid, $result['data']['id']);
        $this->assertEquals('SKU1', $result['data']['sku']);
        $this->assertEquals($pid, $result['data']['product_id']);
        
        // Verify database state
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $vid,
            'sku' => 'SKU1',
            'product_id' => $pid
        ]);
    }

    public function test_show_throws_exception_if_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->show(999);
    }

    public function test_create_variant_respects_route_product_id(): void
    {
        $productIdA = ProductFactory::create(['code' => 'PA', 'name' => 'Product A']);
        $productIdB = ProductFactory::create(['code' => 'PB', 'name' => 'Product B']);

        $data = [
            'product_id' => $productIdB,
            'variant_name' => 'Variant for Product A',
            'sku' => 'SKU_PA_V1',
            'price' => 100.00,
            'cost_price' => 50.00,
            'stock_quantity' => 10,
        ];

        $result = $this->service->create($productIdA, $data);

        // Strong assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('id', $result['data']);
        
        $variantId = $result['data']['id'];
        
        // Verify database state - route product ID should override data product_id
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $variantId,
            'product_id' => $productIdA, // Should use route ID, not data ID
            'variant_name' => 'Variant for Product A',
            'sku' => 'SKU_PA_V1',
            'price' => 100.00
        ]);
        
        // Verify it's NOT linked to product B
        $this->assertDatabaseMissing('product_variants_v2', [
            'id' => $variantId,
            'product_id' => $productIdB
        ]);
    }

    public function test_create_variant_fails_when_sku_matches_product_code(): void
    {
        $productId = ProductFactory::create(['code' => 'CP100', 'name' => 'Product']);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách sản phẩm');
        
        $this->service->create($productId, ['sku' => 'CP100']);
    }

    public function test_update_variant_success(): void
    {
        $pid = ProductFactory::create(['code' => 'P1']);
        $vid = VariantFactory::create($pid, ['sku' => 'SKU1']);

        $result = $this->service->update($vid, ['variant_name' => 'New Name']);
        
        // Strong assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('New Name', $result['data']['variant_name']);

        // Verify database state
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $vid,
            'variant_name' => 'New Name',
            'sku' => 'SKU1'
        ]);
    }

    public function test_update_variant_blocks_duplicate_sku(): void
    {
        $productId = ProductFactory::create(['code' => 'CP200', 'name' => 'Product']);
        $firstVariantId = VariantFactory::create($productId, ['sku' => 'SKU-ONE']);
        $secondVariantId = VariantFactory::create($productId, ['sku' => 'SKU-TWO']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách phiên bản');
        
        $this->service->update($secondVariantId, ['sku' => 'SKU-ONE']);
    }

    public function test_delete_soft_deletes(): void
    {
        $pid = ProductFactory::create(['code' => 'P1']);
        $vid = VariantFactory::create($pid, ['sku' => 'SKU1']);

        $result = $this->service->delete($vid);
        
        // Strong assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        // Verify database state
        $this->assertDatabaseSoftDeleted('product_variants_v2', $vid);
        
        // Verify it's not hard deleted
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $vid,
            'sku' => 'SKU1'
        ]);
    }

    public function test_restore_variant(): void
    {
        $pid = ProductFactory::create(['code' => 'P1']);
        $vid = VariantFactory::create($pid, ['sku' => 'SKU1']);
        $this->service->delete($vid);

        $result = $this->service->restore($vid);
        
        // Strong assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        // Verify database state
        $this->assertDatabaseNotSoftDeleted('product_variants_v2', $vid);
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $vid,
            'sku' => 'SKU1',
            'deleted_at' => null
        ]);
    }

    public function test_hard_delete_variant(): void
    {
        $pid = ProductFactory::create(['code' => 'P1']);
        $vid = VariantFactory::create($pid, ['sku' => 'SKU1']);

        $result = $this->service->hardDelete($vid);
        
        // Strong assertions
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        // Verify database state
        $this->assertDatabaseHardDeleted('product_variants_v2', $vid);
    }

    public function test_uploadMultiple(): void
    {
        $productId = $this->seedProduct(['code' => 'P1']);
        $variantId = $this->seedVariant($productId, 'SKU1');

        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getRandomName')->willReturn('random.jpg');
        $file->expects($this->once())->method('move');

        $result = $this->service->uploadMultiple($variantId, [$file]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['files']);
    }

    public function test_uploadMultiple_fails_if_no_valid_files(): void
    {
        $productId = $this->seedProduct(['code' => 'P1']);
        $variantId = $this->seedVariant($productId, 'SKU1');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->uploadMultiple($variantId, []);
    }

    public function test_attachImages(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        // Seed images
        $this->db->table('product_images')->insert([
            'product_id' => $pid, 'variant_id' => null, 'image_url' => 'img1.jpg', 'created_at' => date('Y-m-d')
        ]);
        $img1 = $this->db->insertID();

        $this->db->table('product_images')->insert([
            'product_id' => $pid, 'variant_id' => $vid, 'image_url' => 'img2.jpg', 'created_at' => date('Y-m-d')
        ]);
        $img2 = $this->db->insertID();

        $result = $this->service->attachImages($vid, [$img1, $img2, 999]);

        $this->assertTrue($result['success']);
        $this->assertContains($img1, $result['attached_ids']);
        $this->assertContains($img2, $result['skipped_ids']); // Already attached
        $this->assertContains(999, $result['missing_ids']);
    }

    /**
     * Test attribute values sync and management
     * @agent-test: Attribute sync functionality
     * @agent-pattern: Full CRUD test with database validation
     */
    public function test_attributeValues_and_sync(): void
    {
        // Use factories instead of hardcoded seeds
        $pid = \Tests\Support\Factories\ProductFactory::create(['code' => 'P1']);
        $vid = \Tests\Support\Factories\VariantFactory::create($pid, ['sku' => 'SKU1']);

        // Seed Attribute using proper schema
        $this->db->table('product_attributes')->insert([
            'name' => 'Color',
            'code' => 'color',
            'type' => 'select',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $attrId = $this->db->insertID();

        // Seed Attribute Option
        $this->db->table('product_attribute_options')->insert([
            'attribute_id' => $attrId,
            'option_value' => 'Red',
            'sort_order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $optionId = $this->db->insertID();

        // Sync attribute values
        $values = [
            ['attribute_id' => $attrId, 'option_id' => $optionId, 'value_text' => 'Red', 'product_id' => $pid]
        ];
        $syncResult = $this->service->syncAttributeValues($vid, $values);
        
        // Strong assertions - not just success flag
        $this->assertTrue($syncResult['success']);
        $this->assertArrayHasKey('data', $syncResult);
        $this->assertCount(1, $syncResult['data']);
        $this->assertEquals($attrId, $syncResult['data'][0]['attribute_id']);
        $this->assertEquals('Red', $syncResult['data'][0]['value_text']);
        
        // Verify database state
        $this->assertDatabaseHas('product_attribute_values', [
            'variant_id' => $vid,
            'attribute_id' => $attrId,
            'value_text' => 'Red'
        ]);

        // Get attribute values
        $getResult = $this->service->attributeValues($vid);
        $this->assertTrue($getResult['success']);
        $this->assertArrayHasKey('data', $getResult);
        $this->assertCount(1, $getResult['data']);
        $this->assertEquals('Color', $getResult['data'][0]['attribute_name']);
        $this->assertEquals('color', $getResult['data'][0]['attribute_code']);
        $this->assertEquals('Red', $getResult['data'][0]['value_text']);

        // Remove attribute from variant
        $removeResult = $this->service->removeAttributeFromVariant($vid, $attrId);
        $this->assertTrue($removeResult['success']);
        $this->assertEquals('Attribute removed from variant', $removeResult['message']);
        
        // Verify removal in database
        $this->assertDatabaseMissing('product_attribute_values', [
            'variant_id' => $vid,
            'attribute_id' => $attrId
        ]);

        // Verify empty result
        $getResultEmpty = $this->service->attributeValues($vid);
        $this->assertTrue($getResultEmpty['success']);
        $this->assertArrayHasKey('data', $getResultEmpty);
        $this->assertCount(0, $getResultEmpty['data']);
    }

    public function test_deletedList(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid1 = $this->seedVariant($pid, 'SKU1');
        $vid2 = $this->seedVariant($pid, 'SKU2');

        $this->service->delete($vid1);

        $result = $this->service->deletedList($pid);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['variants']);
        $this->assertEquals($vid1, $result['data']['variants'][0]['id']);
    }
    
    // ========== EDGE CASE TESTING ==========
    
    public function test_create_variant_with_boundary_values(): void
    {
        $productId = ProductFactory::create(['code' => 'BOUNDARY', 'name' => 'Boundary Test']);
        
        $boundaryTests = [
            'min_price' => [
                'input' => ['sku' => 'MIN', 'variant_name' => 'Min Price', 'price' => 0.01, 'cost_price' => 0.00],
                'should_pass' => true,
                'expected' => ['price' => 0.01, 'cost_price' => 0.00]
            ],
            'max_price' => [
                'input' => ['sku' => 'MAX', 'variant_name' => 'Max Price', 'price' => 999999.99, 'cost_price' => 999999.99],
                'should_pass' => true,
                'expected' => ['price' => 999999.99, 'cost_price' => 999999.99]
            ],
            'negative_price' => [
                'input' => ['sku' => 'NEG', 'variant_name' => 'Negative Price', 'price' => -10],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class,
                'exception_message' => 'price must be positive'
            ],
            'zero_stock' => [
                'input' => ['sku' => 'ZERO', 'variant_name' => 'Zero Stock', 'stock_quantity' => 0],
                'should_pass' => true,
                'expected' => ['stock_quantity' => 0]
            ],
            'negative_stock' => [
                'input' => ['sku' => 'NEGSTOCK', 'variant_name' => 'Negative Stock', 'stock_quantity' => -5],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class,
                'exception_message' => 'stock_quantity must be non-negative'
            ]
        ];
        
        $this->assertBoundaryValues(function($data) use ($productId) {
            return $this->service->create($productId, $data);
        }, $boundaryTests);
    }
    
    public function test_create_variant_with_null_values(): void
    {
        $productId = ProductFactory::create(['code' => 'NULLTEST', 'name' => 'Null Test']);
        
        $this->assertNullValues(function($data) use ($productId) {
            return $this->service->create($productId, $data);
        }, ['sku', 'variant_name']);
    }
    
    public function test_create_variant_with_empty_strings(): void
    {
        $productId = ProductFactory::create(['code' => 'EMPTYTEST', 'name' => 'Empty Test']);
        
        $this->assertEmptyStringValues(function($data) use ($productId) {
            return $this->service->create($productId, $data);
        }, [
            'sku' => ['should_fail' => true, 'message' => 'sku cannot be empty'],
            'variant_name' => ['should_fail' => true, 'message' => 'variant_name cannot be empty']
        ]);
    }
    
    public function test_create_variant_with_max_length_values(): void
    {
        $productId = ProductFactory::create(['code' => 'MAXTEST', 'name' => 'Max Length Test']);
        
        $this->assertMaxLengthValues(function($data) use ($productId) {
            return $this->service->create($productId, $data);
        }, [
            'sku' => 100,
            'variant_name' => 255,
            'barcode' => 100
        ]);
    }
    
    public function test_create_variant_with_special_characters(): void
    {
        $productId = ProductFactory::create(['code' => 'SPECIAL', 'name' => 'Special Test']);
        
        $this->assertSpecialCharacters(function($data) use ($productId) {
            return $this->service->create($productId, $data);
        }, [
            'sku' => true,  // Should sanitize
            'variant_name' => true,  // Should sanitize
            'description' => true  // Should sanitize
        ]);
    }
    
    public function test_variant_list_with_large_dataset(): void
    {
        $productId = ProductFactory::create(['code' => 'LARGE', 'name' => 'Large Dataset Test']);
        
        $this->assertLargeDatasetPerformance(function($data) use ($productId) {
            // Create variants first
            foreach ($data as $index => $variant) {
                VariantFactory::create($productId, [
                    'sku' => 'VAR' . str_pad($index, 4, '0', STR_PAD_LEFT),
                    'variant_name' => 'Variant ' . $index
                ]);
            }
            
            return $this->service->list($productId);
        }, 50);
    }
    
    // ========== ERROR MESSAGE TESTING ==========
    
    public function test_variant_validation_error_messages(): void
    {
        $productId = ProductFactory::create(['code' => 'VALID', 'name' => 'Validation Test']);
        
        $this->assertFieldValidationError('sku', 'SKU is required', function() use ($productId) {
            return $this->service->create($productId, []);
        });
        
        $this->assertFieldValidationError('variant_name', 'Variant name is required', function() use ($productId) {
            return $this->service->create($productId, ['sku' => 'TEST']);
        });
    }
    
    public function test_variant_multiple_field_validation_errors(): void
    {
        $productId = ProductFactory::create(['code' => 'MULTI', 'name' => 'Multi Validation Test']);
        
        $this->assertMultipleFieldValidationErrors([
            'sku' => ['SKU is required'],
            'variant_name' => ['Variant name is required'],
            'price' => ['Price must be numeric']
        ], function() use ($productId) {
            return $this->service->create($productId, []);
        });
    }
    
    public function test_variant_business_rule_violation_messages(): void
    {
        $productId = ProductFactory::create(['code' => 'BUSINESS', 'name' => 'Business Rule Test']);
        
        // Create first variant
        $this->service->create($productId, ['sku' => 'DUPSKU', 'variant_name' => 'First']);
        
        $this->assertBusinessRuleViolation('duplicate_sku', 'SKU đã tồn tại trong danh sách phiên bản', function() use ($productId) {
            return $this->service->create($productId, ['sku' => 'DUPSKU', 'variant_name' => 'Duplicate']);
        });
    }
    
    public function test_variant_constraint_violation_messages(): void
    {
        $productId = ProductFactory::create(['code' => 'CONSTRAINT', 'name' => 'Constraint Test']);
        
        $this->assertConstraintViolation('unique', 'sku', function() use ($productId) {
            // Create first variant
            $this->service->create($productId, ['sku' => 'UNIQUE', 'variant_name' => 'First']);
            // Try to create duplicate
            return $this->service->create($productId, ['sku' => 'UNIQUE', 'variant_name' => 'Second']);
        });
    }
    
    public function test_variant_resource_not_found_error_messages(): void
    {
        $this->assertResourceNotFoundError('Variant', 999, function() {
            return $this->service->show(999);
        });
    }
    
    public function test_variant_standard_error_message_format(): void
    {
        $productId = ProductFactory::create(['code' => 'FORMAT', 'name' => 'Format Test']);
        
        $this->assertStandardErrorMessageFormat(function() use ($productId) {
            return $this->service->create($productId, []);
        });
    }
    
    // ========== BUSINESS LOGIC ASSERTIONS ==========
    
    public function test_create_variant_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'BIZVAR', 'name' => 'Business Logic Variant Test']);
        
        $data = [
            'sku' => 'BIZ001',
            'variant_name' => 'Business Logic Test Variant',
            'price' => 100.50,
            'cost_price' => 75.25,
            'stock_quantity' => 25
        ];
        
        $result = $this->service->create($productId, $data);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertMonetaryPrecision($result['data']['price'], 2);
        $this->assertMonetaryPrecision($result['data']['cost_price'], 2);
        $this->assertTimestampFormat($result['data']['created_at'], 'Y-m-d H:i:s');
        $this->assertStringLength($result['data']['sku'], 1, 100);
        $this->assertStringLength($result['data']['variant_name'], 1, 255);
        $this->assertEquals($productId, $result['data']['product_id']);
        
        // Database state validation
        $this->assertDatabaseHas('product_variants_v2', [
            'sku' => 'BIZ001',
            'variant_name' => 'Business Logic Test Variant',
            'price' => 100.50,
            'cost_price' => 75.25,
            'stock_quantity' => 25,
            'product_id' => $productId,
            'status' => 'active'
        ]);
    }
    
    public function test_update_variant_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'UPDVAR', 'name' => 'Update Variant Test']);
        $variantId = VariantFactory::create($productId, ['sku' => 'OLD001', 'variant_name' => 'Original Variant']);
        
        $updateData = [
            'variant_name' => 'Updated Variant',
            'price' => 150.75,
            'cost_price' => 100.50,
            'stock_quantity' => 50,
            'status' => 'inactive'
        ];
        
        $result = $this->service->update($variantId, $updateData);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertMonetaryPrecision($result['data']['price'], 2);
        $this->assertMonetaryPrecision($result['data']['cost_price'], 2);
        $this->assertTimestampFormat($result['data']['updated_at'], 'Y-m-d H:i:s');
        $this->assertStringEquals($result['data']['variant_name'], 'Updated Variant');
        $this->assertStringEquals($result['data']['status'], 'inactive');
        
        // Database state validation
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $variantId,
            'sku' => 'OLD001', // SKU should not change
            'variant_name' => 'Updated Variant',
            'price' => 150.75,
            'cost_price' => 100.50,
            'stock_quantity' => 50,
            'status' => 'inactive'
        ]);
    }
    
    public function test_delete_variant_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'DELVAR', 'name' => 'Delete Variant Test']);
        $variantId = VariantFactory::create($productId, ['sku' => 'DEL001', 'variant_name' => 'To Delete']);
        
        $result = $this->service->delete($variantId);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertTimestampFormat($result['data']['deleted_at'], 'Y-m-d H:i:s');
        
        // Database state validation
        $this->assertDatabaseSoftDeleted('product_variants_v2', $variantId);
        $this->assertDatabaseHas('product_variants_v2', [
            'id' => $variantId,
            'sku' => 'DEL001',
            'variant_name' => 'To Delete'
        ]);
    }
    
    public function test_variant_sku_collision_business_logic(): void
    {
        // Create product first
        $productId = ProductFactory::create(['code' => 'COLLISION', 'name' => 'Collision Test Product']);
        
        $this->assertBusinessRuleViolation('sku_collision', 'SKU đã tồn tại trong danh sách sản phẩm', function() use ($productId) {
            return $this->service->create($productId, ['sku' => 'COLLISION', 'variant_name' => 'Collision Variant']);
        });
    }
    
    public function test_variant_attribute_sync_business_logic(): void
    {
        $productId = ProductFactory::create(['code' => 'ATTRVAR', 'name' => 'Attribute Variant Test']);
        $variantId = VariantFactory::create($productId, ['sku' => 'ATTR001', 'variant_name' => 'Attribute Variant']);
        
        // Seed attribute
        $this->db->table('product_attributes')->insert([
            'name' => 'Size',
            'code' => 'size',
            'type' => 'select',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $attrId = $this->db->insertID();
        
        // Seed attribute option
        $this->db->table('product_attribute_options')->insert([
            'attribute_id' => $attrId,
            'option_value' => 'Large',
            'sort_order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $optionId = $this->db->insertID();
        
        $values = [
            ['attribute_id' => $attrId, 'option_id' => $optionId, 'value_text' => 'Large', 'product_id' => $productId]
        ];
        
        $result = $this->service->syncAttributeValues($variantId, $values);
        
        // Business logic assertions
        $this->assertBusinessRuleSuccess($result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($attrId, $result['data'][0]['attribute_id']);
        $this->assertEquals('Large', $result['data'][0]['value_text']);
        
        // Database state validation
        $this->assertDatabaseHas('product_attribute_values', [
            'variant_id' => $variantId,
            'attribute_id' => $attrId,
            'value_text' => 'Large'
        ]);
    }
    
    // Legacy seed methods for backward compatibility
    private function seedProduct(array $data): int
    {
        return ProductFactory::create($data);
    }
    
    private function seedVariant(int $productId, string $sku): int
    {
        return VariantFactory::create($productId, ['sku' => $sku]);
    }
}
