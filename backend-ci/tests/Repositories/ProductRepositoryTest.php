<?php

namespace Tests\Repositories;

use App\Repositories\Products\ProductRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Assertions\PerformanceAssertions;
use Tests\Support\Factories\ProductFactory;

/**
 * @agent-test: ProductRepository tests with strong assertions
 * @agent-pattern: Repository test with comprehensive assertions
 * @agent-reusable: HIGH
 */
class ProductRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use PerformanceAssertions;

    private ProductRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        $this->repo = new ProductRepository(null, null, null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_it_finds_all_with_search_filters()
    {
        // Create test products using factory
        $productId1 = ProductFactory::create(['code' => 'AO01', 'name' => 'ao so mi']);
        $productId2 = ProductFactory::create(['code' => 'QU01', 'name' => 'quan jean']);
        $productId3 = ProductFactory::create(['code' => 'AO02', 'name' => 'ao khoac']);

        $result = $this->repo->findAll(['search' => 'ao', 'page' => 1, 'limit' => 10]);

        // Strong assertions instead of weak assertions
        $this->assertCount(2, $result);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseHas('products', ['code' => 'AO01', 'name' => 'ao so mi']);
        $this->assertDatabaseHas('products', ['code' => 'AO02', 'name' => 'ao khoac']);
        $this->assertDatabaseMissing('products', ['code' => 'QU01', 'name' => 'quan jean']); // Should not be in search results
        
        // Verify result structure
        foreach ($result as $product) {
            $this->assertArrayHasKey('id', $product);
            $this->assertArrayHasKey('code', $product);
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('status', $product);
            $this->assertArrayHasKey('selling_price', $product);
        }
    }

    public function test_it_soft_deletes_products_correctly()
    {
        $productId = ProductFactory::create(['code' => 'DEL01', 'name' => 'delete item']);

        // Verify product exists before deletion
        $this->assertDatabaseHas('products', ['id' => $productId, 'code' => 'DEL01']);
        $this->assertDatabaseNotSoftDeleted('products', $productId);

        $this->assertTrue($this->repo->delete($productId));

        // Strong assertions for soft delete
        $this->assertDatabaseSoftDeleted('products', $productId);
        $this->assertDatabaseHas('products', ['id' => $productId, 'code' => 'DEL01']); // Record still exists
        $this->assertDatabaseCount('products', 1); // Still counted in total
        $this->assertSame(0, $this->repo->count(['page' => 1, 'limit' => 10])); // But not in active count
    }

    public function test_it_checks_code_exists_with_exclusions()
    {
        $productId = ProductFactory::create(['code' => 'EX01', 'name' => 'exists']);

        // Strong assertions for code existence checks
        $this->assertTrue($this->repo->codeExists('EX01'));
        $this->assertFalse($this->repo->codeExists('EX01', $productId)); // Exclude self
        $this->assertFalse($this->repo->codeExists('NEW')); // Non-existent code
        
        // Database state validation
        $this->assertDatabaseHas('products', ['code' => 'EX01', 'id' => $productId]);
        $this->assertDatabaseMissing('products', ['code' => 'NEW']);
    }

    public function test_it_handles_boundary_values_for_pagination()
    {
        // Create test data
        $productIds = ProductFactory::createMany(25, [
            'name' => 'Test Product',
            'status' => 'active'
        ]);

        // Test boundary values
        $boundaryTests = [
            'page_1_limit_10' => [
                'input' => ['page' => 1, 'limit' => 10],
                'expected' => 10,
                'should_pass' => true
            ],
            'page_3_limit_10' => [
                'input' => ['page' => 3, 'limit' => 10],
                'expected' => 5,
                'should_pass' => true
            ],
            'invalid_page_zero' => [
                'input' => ['page' => 0, 'limit' => 10],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class
            ],
            'invalid_limit_zero' => [
                'input' => ['page' => 1, 'limit' => 0],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class
            ],
            'excessive_limit' => [
                'input' => ['page' => 1, 'limit' => 1000],
                'expected' => 25,
                'should_pass' => true
            ]
        ];

        $this->assertBoundaryValues(function($filters) {
            return ['success' => true, 'data' => $this->repo->findAll($filters)];
        }, $boundaryTests);
    }

    public function test_it_handles_null_values_for_required_fields()
    {
        $requiredFields = ['code', 'name', 'product_type', 'status'];
        
        $this->assertNullValues(function($data) {
            return $this->repo->create($data);
        }, $requiredFields);
    }

    public function test_it_handles_empty_string_values()
    {
        $fields = [
            'code' => ['should_fail' => true, 'message' => 'code cannot be empty'],
            'name' => ['should_fail' => true, 'message' => 'name cannot be empty'],
            'barcode' => ['should_fail' => false, 'message' => 'barcode can be empty']
        ];

        $this->assertEmptyStringValues(function($data) {
            return $this->repo->create($data);
        }, $fields);
    }

    public function test_it_handles_max_length_values()
    {
        $fields = [
            'code' => 50,
            'name' => 255,
            'barcode' => 100
        ];

        $this->assertMaxLengthValues(function($data) {
            return $this->repo->create($data);
        }, $fields);
    }

    public function test_it_handles_special_characters()
    {
        $productId = ProductFactory::create(['code' => 'SPECIAL', 'name' => 'Special Test']);
        
        $testCases = [
            'name' => true, // Should sanitize
            'code' => false, // Should not sanitize
            'barcode' => true // Should sanitize
        ];

        $this->assertSpecialCharacters(function($data) use ($productId) {
            return $this->repo->update($productId, $data);
        }, $testCases);
    }

    public function test_it_handles_large_dataset_performance()
    {
        $this->assertLargeDatasetPerformance(function($testData) {
            $ids = [];
            foreach ($testData as $product) {
                $ids[] = ProductFactory::create($product);
            }
            
            // Test search performance on large dataset
            $result = $this->repo->findAll(['search' => 'Test', 'page' => 1, 'limit' => 50]);
            return ['success' => true, 'data' => $result];
        }, 100, 1000); // 100 records, 1000ms max time
    }

    public function test_it_enforces_unique_code_constraint()
    {
        $productId1 = ProductFactory::create(['code' => 'UNIQUE01', 'name' => 'First Product']);
        
        $this->assertUniqueConstraint(function() {
            return ProductFactory::create(['code' => 'UNIQUE01', 'name' => 'Second Product']);
        }, 'code');
    }

    public function test_it_validates_foreign_key_constraints()
    {
        $this->assertForeignKeyConstraint(function() {
            return $this->repo->create([
                'code' => 'FK_TEST',
                'name' => 'FK Test',
                'product_type' => 'goods',
                'status' => 'active',
                'category_id' => 99999 // Non-existent category
            ]);
        }, 'category_id');
    }

    public function test_it_handles_date_time_edge_cases()
    {
        $dateTests = [
            'created_at' => 'future_date',
            'updated_at' => 'past_date',
            'start_date' => 'invalid_date'
        ];

        $this->assertTemporalEdgeCases(function($data) {
            return $this->repo->create($data);
        }, $dateTests);
    }

    public function test_it_validates_data_types()
    {
        $typeTests = [
            'selling_price' => [
                'expected_type' => 'numeric',
                'valid' => 99.99,
                'invalid' => 'not_a_number'
            ],
            'status' => [
                'expected_type' => 'string',
                'valid' => 'active',
                'invalid' => 123
            ]
        ];

        $this->assertDataTypeValidation(function($data) {
            return $this->repo->create($data);
        }, $typeTests);
    }

    public function test_it_handles_concurrent_access()
    {
        $productId = ProductFactory::create(['code' => 'CONCURRENT', 'name' => 'Concurrent Test']);
        
        $callback1 = function() use ($productId) {
            return $this->repo->update($productId, ['name' => 'Updated 1']);
        };
        
        $callback2 = function() use ($productId) {
            return $this->repo->update($productId, ['name' => 'Updated 2']);
        };

        $this->assertConcurrentAccess($callback1, $callback2);
    }

    public function test_it_handles_transaction_rollback()
    {
        $successCallback = function() {
            $productId = ProductFactory::create(['code' => 'TX_SUCCESS', 'name' => 'Transaction Success']);
            return ['success' => true, 'data' => $productId];
        };

        $failCallback = function() {
            $this->db->transException(true);
            $this->db->transStart();
            
            ProductFactory::create(['code' => 'TX_FAIL1', 'name' => 'Transaction Fail 1']);
            ProductFactory::create(['code' => 'TX_FAIL2', 'name' => 'Transaction Fail 2']);
            
            // Force an error
            $this->db->table('non_existent_table')->insert(['test' => 'value']);
            
            $this->db->transComplete();
        };

        $this->assertTransactionRollback($successCallback, $failCallback);
    }

    public function test_it_validates_monetary_precision()
    {
        $productId = ProductFactory::create(['code' => 'PRECISION', 'name' => 'Precision Test', 'selling_price' => 99.99]);
        
        $product = $this->repo->findById($productId);
        $this->assertMonetaryPrecision($product['selling_price'], 2);
        
        // Test with different precision values
        $this->repo->update($productId, ['selling_price' => 100.123]);
        $updatedProduct = $this->repo->findById($productId);
        $this->assertMonetaryPrecision($updatedProduct['selling_price'], 2);
    }

    public function test_it_validates_response_structure()
    {
        $productId = ProductFactory::create(['code' => 'STRUCTURE', 'name' => 'Structure Test']);
        
        $product = $this->repo->findById($productId);
        
        // Validate expected fields exist
        $expectedFields = ['id', 'code', 'name', 'product_type', 'status', 'selling_price', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $product);
        }
        
        // Validate data types
        $this->assertIsInt($product['id']);
        $this->assertIsString($product['code']);
        $this->assertIsString($product['name']);
        $this->assertIsString($product['product_type']);
        $this->assertIsString($product['status']);
        $this->assertIsNumeric($product['selling_price']);
    }

    public function test_it_handles_memory_usage()
    {
        $this->assertMemoryUsage(function() {
            // Create a large dataset and fetch it
            $productIds = ProductFactory::createMany(500);
            $result = $this->repo->findAll(['page' => 1, 'limit' => 500]);
            return ['success' => true, 'data' => $result];
        }, 50); // 50MB limit
    }
    
    public function test_it_handles_execution_time_performance()
    {
        $this->assertExecutionTime(function() {
            // Create test data
            ProductFactory::createMany(100);
            
            // Test search performance
            $result = $this->repo->findAll(['search' => 'Test', 'page' => 1, 'limit' => 50]);
            return $result;
        }, 500); // 500ms max
    }
    
    public function test_it_handles_query_count_performance()
    {
        $this->assertQueryCount(function() {
            // Create test data
            ProductFactory::createMany(50);
            
            // Test that findAll doesn't cause N+1 queries
            $result = $this->repo->findAll(['page' => 1, 'limit' => 50]);
            return $result;
        }, 5); // Max 5 queries
    }
    
    public function test_it_handles_pagination_performance()
    {
        // Create a larger dataset for pagination testing
        ProductFactory::createMany(200);
        
        $this->assertPaginationPerformance(function($params) {
            return $this->repo->findAll($params);
        }, 200, 300); // 200 records, 300ms max
    }
    
    public function test_it_handles_search_performance()
    {
        // Create test data with searchable content
        for ($i = 0; $i < 100; $i++) {
            ProductFactory::create([
                'name' => 'Searchable Product ' . $i,
                'code' => 'SEARCH' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ]);
        }
        
        $this->assertSearchPerformance(function($params) {
            return $this->repo->findAll($params);
        }, 100, 400); // 100 records, 400ms max
    }
}
