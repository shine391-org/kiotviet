<?php

namespace Tests\Repositories;

use App\Repositories\Inventory\ReorderLevelRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Factories\ReorderLevelFactory;
use Tests\Support\Factories\ProductFactory;

/**
 * @agent-test: ReorderLevelRepository tests with strong assertions
 * @agent-pattern: Repository test with comprehensive assertions
 * @agent-reusable: HIGH
 */
class ReorderLevelRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;

    private ReorderLevelRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->repo = new ReorderLevelRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_updates_reorder_levels()
    {
        $level = $this->repo->create([
            'product_id' => 1,
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);

        // Strong assertions for creation
        $this->assertNotEmpty($level['id']);
        $this->assertEquals(1, (int) $level['branch_id']);
        $this->assertTrue((bool) $level['is_active']);
        
        // Database state validation
        $this->assertDatabaseHas('reorder_levels', [
            'id' => $level['id'],
            'product_id' => 1,
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
            'is_active' => 1
        ]);

        $updated = $this->repo->update($level['id'], ['max_level' => 20, 'is_active' => false]);
        
        // Strong assertions for update
        $this->assertEquals(20.0, (float) $updated['max_level']);
        $this->assertFalse((bool) $updated['is_active']);
        
        // Database state validation for update
        $this->assertDatabaseHas('reorder_levels', [
            'id' => $level['id'],
            'max_level' => 20,
            'is_active' => 0
        ]);
    }

    /** @test */
    public function it_returns_shortages_with_available_quantities()
    {
        // Create test products
        $productId1 = ProductFactory::create(['code' => 'SHORTAGE1', 'name' => 'Shortage Product 1']);
        $productId2 = ProductFactory::create(['code' => 'SHORTAGE2', 'name' => 'Shortage Product 2']);
        
        // Create reorder levels
        $reorderId1 = ReorderLevelFactory::createForProduct($productId1, [
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);
        
        $reorderId2 = ReorderLevelFactory::createForProduct($productId2, [
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);

        // Create stock bins data
        $now = date('Y-m-d H:i:s');
        $this->db->table('stock_bins')->insert([
            'product_id' => $productId1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 4,
            'reserved_qty' => 1,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => $productId2,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 15,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);

        $shortages = $this->repo->findShortages([]);
        
        // Strong assertions
        $this->assertCount(1, $shortages);
        $this->assertEquals($productId1, $shortages[0]['product_id']);
        $this->assertEquals(3.0, (float) $shortages[0]['available_qty']); // 4 - 1
        $this->assertEquals(4.0, (float) $shortages[0]['on_hand_qty']);
        $this->assertEquals(1.0, (float) $shortages[0]['reserved_qty']);
        $this->assertEquals(5.0, (float) $shortages[0]['min_level']); // Below min level
        
        // Database state validation
        $this->assertDatabaseHas('stock_bins', [
            'product_id' => $productId1,
            'on_hand_qty' => 4,
            'reserved_qty' => 1
        ]);
        $this->assertDatabaseHas('stock_bins', [
            'product_id' => $productId2,
            'on_hand_qty' => 15,
            'reserved_qty' => 0
        ]);
    }

    /** @test */
    public function it_handles_boundary_values_for_levels()
    {
        // Test boundary values for reorder levels
        $boundaryTests = [
            'zero_min_level' => [
                'input' => ['min_level' => 0, 'max_level' => 10, 'safety_stock' => 0],
                'should_pass' => true
            ],
            'negative_min_level' => [
                'input' => ['min_level' => -1, 'max_level' => 10, 'safety_stock' => 0],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class
            ],
            'min_greater_than_max' => [
                'input' => ['min_level' => 15, 'max_level' => 10, 'safety_stock' => 0],
                'should_pass' => false,
                'exception' => \InvalidArgumentException::class
            ],
            'max_boundary' => [
                'input' => ['min_level' => 1, 'max_level' => 999999, 'safety_stock' => 0],
                'should_pass' => true
            ]
        ];

        $this->assertBoundaryValues(function($data) {
            $productId = ProductFactory::create(['code' => 'BOUNDARY', 'name' => 'Boundary Test']);
            $levelData = array_merge([
                'product_id' => $productId,
                'branch_id' => 1,
                'safety_stock' => 1
            ], $data);
            
            return $this->repo->create($levelData);
        }, $boundaryTests);
    }

    /** @test */
    public function it_handles_null_values_for_required_fields()
    {
        $requiredFields = ['product_id', 'branch_id', 'min_level', 'max_level'];
        
        $this->assertNullValues(function($data) {
            return $this->repo->create($data);
        }, $requiredFields);
    }

    /** @test */
    public function it_handles_empty_string_values()
    {
        // Most fields are numeric, but test any string fields
        $fields = [
            'notes' => ['should_fail' => false, 'message' => 'notes can be empty']
        ];

        $this->assertEmptyStringValues(function($data) {
            $productId = ProductFactory::create(['code' => 'EMPTY', 'name' => 'Empty Test']);
            $levelData = array_merge([
                'product_id' => $productId,
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2
            ], $data);
            
            return $this->repo->create($levelData);
        }, $fields);
    }

    /** @test */
    public function it_handles_max_length_values()
    {
        $fields = [
            'notes' => 1000
        ];

        $this->assertMaxLengthValues(function($data) {
            $productId = ProductFactory::create(['code' => 'MAXLEN', 'name' => 'Max Length Test']);
            $levelData = array_merge([
                'product_id' => $productId,
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2
            ], $data);
            
            return $this->repo->create($levelData);
        }, $fields);
    }

    /** @test */
    public function it_handles_special_characters()
    {
        $productId = ProductFactory::create(['code' => 'SPECIAL', 'name' => 'Special Test']);
        $reorderId = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
        
        $testCases = [
            'notes' => true // Should sanitize
        ];

        $this->assertSpecialCharacters(function($data) use ($reorderId) {
            return $this->repo->update($reorderId, $data);
        }, $testCases);
    }

    /** @test */
    public function it_handles_large_dataset_performance()
    {
        $this->assertLargeDatasetPerformance(function($data) {
            $ids = [];
            foreach ($data as $index => $level) {
                $productId = ProductFactory::create(['code' => 'PERF' . str_pad($index, 4, '0', STR_PAD_LEFT)]);
                $levelData = array_merge(['product_id' => $productId, 'branch_id' => 1], $level);
                $ids[] = $this->repo->create($levelData);
            }
            
            // Test search performance on large dataset
            $result = $this->repo->findAll(['page' => 1, 'limit' => 50]);
            return ['success' => true, 'data' => $result];
        }, 1000);
    }

    /** @test */
    public function it_enforces_unique_product_branch_constraint()
    {
        $productId = ProductFactory::create(['code' => 'UNIQUE', 'name' => 'Unique Test']);
        
        $reorderId1 = ReorderLevelFactory::createForProductAndBranch($productId, 1);
        
        $this->assertUniqueConstraint(function() use ($productId) {
            return ReorderLevelFactory::createForProductAndBranch($productId, 1);
        }, 'product_id,branch_id');
    }

    /** @test */
    public function it_validates_foreign_key_constraints()
    {
        $this->assertForeignKeyConstraint(function() {
            return $this->repo->create([
                'product_id' => 99999, // Non-existent product
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2
            ]);
        }, 'product_id');
    }

    /** @test */
    public function it_handles_date_time_edge_cases()
    {
        $productId = ProductFactory::create(['code' => 'DATE', 'name' => 'Date Test']);
        
        $dateTests = [
            'created_at' => 'future_date',
            'updated_at' => 'past_date'
        ];

        $this->assertTemporalEdgeCases(function($data) use ($productId) {
            $levelData = array_merge([
                'product_id' => $productId,
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2
            ], $data);
            
            return $this->repo->create($levelData);
        }, $dateTests);
    }

    /** @test */
    public function it_validates_data_types()
    {
        $typeTests = [
            'min_level' => [
                'expected_type' => 'numeric',
                'valid' => 5,
                'invalid' => 'not_a_number'
            ],
            'max_level' => [
                'expected_type' => 'numeric',
                'valid' => 10,
                'invalid' => 'not_a_number'
            ],
            'safety_stock' => [
                'expected_type' => 'numeric',
                'valid' => 2,
                'invalid' => 'not_a_number'
            ],
            'is_active' => [
                'expected_type' => 'numeric',
                'valid' => 1,
                'invalid' => 'not_a_number'
            ]
        ];

        $this->assertDataTypeValidation(function($data) {
            $productId = ProductFactory::create(['code' => 'TYPE', 'name' => 'Type Test']);
            $levelData = array_merge([
                'product_id' => $productId,
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2
            ], $data);
            
            return $this->repo->create($levelData);
        }, $typeTests);
    }

    /** @test */
    public function it_soft_deletes_reorder_levels()
    {
        $productId = ProductFactory::create(['code' => 'SOFTDEL', 'name' => 'Soft Delete Test']);
        $reorderId = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
        
        // Verify record exists before deletion
        $this->assertDatabaseHas('reorder_levels', ['id' => $reorderId]);
        $this->assertDatabaseNotSoftDeleted('reorder_levels', $reorderId);

        $this->repo->delete($reorderId);
        
        // Strong assertions for soft delete
        $this->assertDatabaseSoftDeleted('reorder_levels', $reorderId);
        $this->assertDatabaseHas('reorder_levels', ['id' => $reorderId]); // Record still exists
        
        // Verify soft delete timestamp
        $row = $this->db->table('reorder_levels')->where('id', $reorderId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
        $this->assertTimestampIsRecent($row['deleted_at']);
    }

    /** @test */
    public function it_validates_response_structure()
    {
        $productId = ProductFactory::create(['code' => 'STRUCTURE', 'name' => 'Structure Test']);
        $reorderId = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
        
        $level = $this->repo->findById($reorderId);
        
        // Validate expected fields exist
        $expectedFields = ['id', 'product_id', 'branch_id', 'min_level', 'max_level', 'safety_stock', 'reorder_point', 'reorder_quantity', 'is_active', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $level);
        }
        
        // Validate data types
        $this->assertIsInt($level['id']);
        $this->assertIsInt($level['product_id']);
        $this->assertIsInt($level['branch_id']);
        $this->assertIsNumeric($level['min_level']);
        $this->assertIsNumeric($level['max_level']);
        $this->assertIsNumeric($level['safety_stock']);
        $this->assertIsNumeric($level['is_active']);
    }

    /** @test */
    public function it_handles_memory_usage()
    {
        $this->assertMemoryUsage(function() {
            // Create a large dataset and fetch it
            $ids = [];
            for ($i = 0; $i < 500; $i++) {
                $productId = ProductFactory::create(['code' => 'MEM' . str_pad($i, 4, '0', STR_PAD_LEFT)]);
                $ids[] = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
            }
            
            $result = $this->repo->findAll(['page' => 1, 'limit' => 500]);
            return ['success' => true, 'data' => $result];
        }, 50); // 50MB limit
    }

    /** @test */
    public function it_calculates_reorder_point_correctly()
    {
        $productId = ProductFactory::create(['code' => 'REORDER', 'name' => 'Reorder Test']);
        
        // Create reorder level with specific values
        $reorderId = ReorderLevelFactory::createWithLevels(10, 50, [
            'product_id' => $productId,
            'branch_id' => 1,
            'safety_stock' => 5
        ]);
        
        $level = $this->repo->findById($reorderId);
        
        // Reorder point should be min_level + safety_stock
        $this->assertEquals(15, (float) $level['reorder_point']); // 10 + 5
        
        // Database state validation
        $this->assertDatabaseHas('reorder_levels', [
            'id' => $reorderId,
            'min_level' => 10,
            'safety_stock' => 5,
            'reorder_point' => 15
        ]);
    }

    /** @test */
    public function it_filters_by_branch_and_product()
    {
        $productId1 = ProductFactory::create(['code' => 'FILTER1', 'name' => 'Filter Product 1']);
        $productId2 = ProductFactory::create(['code' => 'FILTER2', 'name' => 'Filter Product 2']);
        
        // Create reorder levels for different branches and products
        $reorderId1 = ReorderLevelFactory::createForProductAndBranch($productId1, 1);
        $reorderId2 = ReorderLevelFactory::createForProductAndBranch($productId1, 2);
        $reorderId3 = ReorderLevelFactory::createForProductAndBranch($productId2, 1);
        
        // Test filtering by branch
        $branch1Results = $this->repo->findAll(['branch_id' => 1]);
        $branch2Results = $this->repo->findAll(['branch_id' => 1]);
        
        $branch1Ids = array_column($branch1Results, 'id');
        $this->assertContains($reorderId1, $branch1Ids);
        $this->assertContains($reorderId3, $branch1Ids);
        $this->assertNotContains($reorderId2, $branch1Ids);
        
        // Test filtering by product
        $product1Results = $this->repo->findAll(['product_id' => $productId1]);
        $product1Ids = array_column($product1Results, 'id');
        $this->assertContains($reorderId1, $product1Ids);
        $this->assertContains($reorderId2, $product1Ids);
        $this->assertNotContains($reorderId3, $product1Ids);
    }

    /** @test */
    public function it_handles_concurrent_access()
    {
        $productId = ProductFactory::create(['code' => 'CONCURRENT', 'name' => 'Concurrent Test']);
        $reorderId = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
        
        $callback1 = function() use ($reorderId) {
            return $this->repo->update($reorderId, ['min_level' => 10]);
        };
        
        $callback2 = function() use ($reorderId) {
            return $this->repo->update($reorderId, ['max_level' => 50]);
        };

        $this->assertConcurrentAccess($callback1, $callback2);
    }

    /** @test */
    public function it_handles_transaction_rollback()
    {
        $successCallback = function() {
            $productId = ProductFactory::create(['code' => 'TX_SUCCESS', 'name' => 'Transaction Success']);
            $reorderId = ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
            return ['success' => true, 'data' => $reorderId];
        };

        $failCallback = function() {
            $this->db->transException(true);
            $this->db->transStart();
            
            $productId = ProductFactory::create(['code' => 'TX_FAIL', 'name' => 'Transaction Fail']);
            ReorderLevelFactory::createForProduct($productId, ['branch_id' => 1]);
            
            // Force an error
            $this->db->table('non_existent_table')->insert(['test' => 'value']);
            
            $this->db->transComplete();
        };

        $this->assertTransactionRollback($successCallback, $failCallback);
    }
}
