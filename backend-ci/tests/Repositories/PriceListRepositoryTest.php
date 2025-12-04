<?php

namespace Tests\Repositories;

use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Factories\PriceListFactory;
use Tests\Support\Factories\ProductFactory;

/**
 * @agent-test: PriceListRepository tests with strong assertions
 * @agent-pattern: Repository test with comprehensive assertions
 * @agent-reusable: HIGH
 */
class PriceListRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;

    private PriceListRepository $repo;
    private PriceListItemRepository $itemRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPriceListSchema();
        $this->repo = new PriceListRepository(null, $this->db);
        $this->itemRepo = new PriceListItemRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_finds_applicable_lists_with_filters()
    {
        // Create test data using factories
        $vipListId = PriceListFactory::createVip(['name' => 'VIP Test', 'apply_to_groups' => [2]]);
        $retailListId = PriceListFactory::createCustom(['name' => 'Retail Test', 'priority' => 1]);
        $inactiveListId = PriceListFactory::createInactive(['name' => 'Inactive Test']);

        $result = $this->repo->applicablePriceLists(2, date('Y-m-d'));
        $ids = array_column($result, 'id');

        // Strong assertions
        $this->assertCount(2, $result); // Only active lists
        $this->assertContains($vipListId, $ids);
        $this->assertContains($retailListId, $ids);
        $this->assertNotContains($inactiveListId, $ids); // Inactive list should not be included
        
        // Database state validation
        $this->assertDatabaseCount('price_lists', 3);
        $this->assertDatabaseHas('price_lists', ['id' => $vipListId, 'is_active' => 1]);
        $this->assertDatabaseHas('price_lists', ['id' => $retailListId, 'is_active' => 1]);
        $this->assertDatabaseHas('price_lists', ['id' => $inactiveListId, 'is_active' => 0]);
        
        // Verify result structure
        foreach ($result as $list) {
            $this->assertArrayHasKey('id', $list);
            $this->assertArrayHasKey('name', $list);
            $this->assertArrayHasKey('type', $list);
            $this->assertArrayHasKey('priority', $list);
            $this->assertArrayHasKey('is_active', $list);
        }
    }

    /** @test */
    public function it_bulk_upserts_items_correctly()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Bulk Test']);
        $count = 50;
        
        // Create products first
        $productIds = ProductFactory::createMany($count);
        
        // Create items data
        $items = [];
        foreach ($productIds as $index => $productId) {
            $items[] = ['product_id' => $productId, 'price' => 100 + $index];
        }

        // Verify list exists before adding items
        $this->assertDatabaseHas('price_lists', ['id' => $listId]);
        $this->assertDatabaseCount('price_list_items', 0);

        $res = $this->itemRepo->replaceItems($listId, $items);
        
        // Strong assertions
        $this->assertEquals($count, $res['inserted']);
        $this->assertDatabaseCount('price_list_items', $count);
        
        // Verify items were inserted correctly
        foreach ($items as $item) {
            $this->assertDatabaseHas('price_list_items', [
                'price_list_id' => $listId,
                'product_id' => $item['product_id'],
                'price' => $item['price']
            ]);
        }
    }

    /** @test */
    public function it_handles_large_bulk_insert_performance()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Large Bulk Test']);
        $count = 1000;
        
        // Create products first
        $productIds = ProductFactory::createMany($count);
        
        // Create items data
        $rows = [];
        foreach ($productIds as $index => $productId) {
            $rows[] = ['product_id' => $productId, 'price' => 10000 + $index];
        }

        $startTime = microtime(true);
        $res = $this->itemRepo->replaceItems($listId, $rows);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertion
        $this->assertLessThan(5.0, $executionTime, 
            "Large bulk insert should complete within 5 seconds, took {$executionTime}");
        
        // Strong assertions
        $this->assertEquals($count, $res['inserted']);
        $this->assertDatabaseCount('price_list_items', $count);
    }

    /** @test */
    public function it_imports_multiple_lists_with_items()
    {
        $itemsPerList = 1000;
        $lists = 50;

        // Create products first
        ProductFactory::createMany($itemsPerList);

        $totalInserted = 0;

        for ($i = 1; $i <= $lists; $i++) {
            $listId = PriceListFactory::createCustom(['name' => 'Perf-' . $i]);
            
            // Create items for this list
            $rows = [];
            for ($p = 1; $p <= $itemsPerList; $p++) {
                $rows[] = ['product_id' => $p, 'price' => 10000 + $p];
            }
            
            $res = $this->itemRepo->replaceItems($listId, $rows);
            $totalInserted += $res['inserted'];
        }

        // Strong assertions
        $this->assertEquals($lists * $itemsPerList, $totalInserted);
        $this->assertDatabaseCount('price_lists', $lists);
        $this->assertDatabaseCount('price_list_items', $lists * $itemsPerList);
    }

    /** @test */
    public function it_imports_items_from_different_formats()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Import Test']);
        
        // Create products first
        ProductFactory::createMany(5);

        // CSV format simulation
        $csvItems = [
            ['product_id' => 1, 'price' => 101],
            ['product_id' => 2, 'price' => 202]
        ];

        // JSON format simulation
        $jsonItems = [
            ['product_id' => 3, 'price' => 303],
            ['product_id' => 4, 'price' => 404],
            ['product_id' => 5, 'price' => 505]
        ];

        $rows = array_merge($csvItems, $jsonItems);
        $res = $this->itemRepo->replaceItems($listId, $rows);

        // Strong assertions
        $this->assertEquals(count($rows), $res['inserted']);
        $this->assertDatabaseCount('price_list_items', count($rows));
        
        // Verify all items were inserted
        foreach ($rows as $item) {
            $this->assertDatabaseHas('price_list_items', [
                'price_list_id' => $listId,
                'product_id' => $item['product_id'],
                'price' => $item['price']
            ]);
        }
    }

    /** @test */
    public function it_soft_deletes_preserves_row()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Soft Delete Test']);
        
        // Verify list exists before deletion
        $this->assertDatabaseHas('price_lists', ['id' => $listId]);
        $this->assertDatabaseNotSoftDeleted('price_lists', $listId);

        $this->repo->delete($listId);
        
        // Strong assertions for soft delete
        $this->assertDatabaseSoftDeleted('price_lists', $listId);
        $this->assertDatabaseHas('price_lists', ['id' => $listId]); // Record still exists
        
        // Verify soft delete timestamp
        $row = $this->db->table('price_lists')->where('id', $listId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
        $this->assertTimestampIsRecent($row['deleted_at']);
    }

    /** @test */
    public function it_handles_boundary_values_for_pagination()
    {
        // Create test data
        $listIds = PriceListFactory::createMany(25, ['name' => 'Test List']);

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

    /** @test */
    public function it_handles_null_values_for_required_fields()
    {
        $requiredFields = ['name', 'type'];
        
        $this->assertNullValues(function($data) {
            return PriceListFactory::create($data);
        }, $requiredFields);
    }

    /** @test */
    public function it_handles_empty_string_values()
    {
        $fields = [
            'name' => ['should_fail' => true, 'message' => 'name cannot be empty'],
            'type' => ['should_fail' => true, 'message' => 'type cannot be empty'],
            'description' => ['should_fail' => false, 'message' => 'description can be empty']
        ];

        $this->assertEmptyStringValues(function($data) {
            return PriceListFactory::create($data);
        }, $fields);
    }

    /** @test */
    public function it_handles_max_length_values()
    {
        $fields = [
            'name' => 255,
            'type' => 50,
            'description' => 1000
        ];

        $this->assertMaxLengthValues(function($data) {
            return PriceListFactory::create($data);
        }, $fields);
    }

    /** @test */
    public function it_handles_special_characters()
    {
        $listId = PriceListFactory::create(['name' => 'Special Test']);
        
        $testCases = [
            'name' => true, // Should sanitize
            'type' => false, // Should not sanitize
            'description' => true // Should sanitize
        ];

        $this->assertSpecialCharacters(function($data) use ($listId) {
            return $this->repo->update($listId, $data);
        }, $testCases);
    }

    /** @test */
    public function it_handles_large_dataset_performance()
    {
        $this->assertLargeDatasetPerformance(function($data) {
            $ids = [];
            foreach ($data as $list) {
                $ids[] = PriceListFactory::create($list);
            }
            
            // Test search performance on large dataset
            $result = $this->repo->findAll(['search' => 'Test', 'page' => 1, 'limit' => 50]);
            return ['success' => true, 'data' => $result];
        }, 1000);
    }

    /** @test */
    public function it_enforces_unique_name_constraint()
    {
        $listId1 = PriceListFactory::create(['name' => 'UNIQUE_NAME', 'type' => 'retail']);
        
        $this->assertUniqueConstraint(function() {
            return PriceListFactory::create(['name' => 'UNIQUE_NAME', 'type' => 'custom']);
        }, 'name');
    }

    /** @test */
    public function it_validates_foreign_key_constraints()
    {
        $this->assertForeignKeyConstraint(function() {
            return $this->itemRepo->replaceItems(999, [
                ['product_id' => 99999, 'price' => 100] // Non-existent product
            ]);
        }, 'product_id');
    }

    /** @test */
    public function it_handles_date_time_edge_cases()
    {
        $dateTests = [
            'start_date' => 'future_date',
            'end_date' => 'past_date',
            'created_at' => 'invalid_date'
        ];

        $this->assertTemporalEdgeCases(function($data) {
            return PriceListFactory::create($data);
        }, $dateTests);
    }

    /** @test */
    public function it_validates_data_types()
    {
        $typeTests = [
            'priority' => [
                'expected_type' => 'numeric',
                'valid' => 5,
                'invalid' => 'not_a_number'
            ],
            'is_active' => [
                'expected_type' => 'numeric',
                'valid' => 1,
                'invalid' => 'not_a_number'
            ]
        ];

        $this->assertDataTypeValidation(function($data) {
            return PriceListFactory::create($data);
        }, $typeTests);
    }

    /** @test */
    public function it_validates_monetary_precision()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Precision Test']);
        ProductFactory::createMany(2);
        
        $items = [
            ['product_id' => 1, 'price' => 99.99],
            ['product_id' => 2, 'price' => 100.123]
        ];

        $this->itemRepo->replaceItems($listId, $items);
        
        // Verify monetary precision
        $listItems = $this->db->table('price_list_items')
            ->where('price_list_id', $listId)
            ->get()
            ->getResultArray();
            
        foreach ($listItems as $item) {
            $this->assertMonetaryPrecision($item['price'], 2);
        }
    }

    /** @test */
    public function it_validates_response_structure()
    {
        $listId = PriceListFactory::createCustom(['name' => 'Structure Test']);
        
        $list = $this->repo->findById($listId);
        
        // Validate expected fields exist
        $expectedFields = ['id', 'name', 'type', 'priority', 'is_active', 'start_date', 'end_date', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $list);
        }
        
        // Validate data types
        $this->assertIsInt($list['id']);
        $this->assertIsString($list['name']);
        $this->assertIsString($list['type']);
        $this->assertIsNumeric($list['priority']);
        $this->assertIsNumeric($list['is_active']);
    }

    /** @test */
    public function it_handles_memory_usage()
    {
        $this->assertMemoryUsage(function() {
            // Create a large dataset and fetch it
            $listIds = PriceListFactory::createMany(500);
            $result = $this->repo->findAll(['page' => 1, 'limit' => 500]);
            return ['success' => true, 'data' => $result];
        }, 50); // 50MB limit
    }

    /** @test */
    public function it_handles_priority_ordering()
    {
        // Create lists with different priorities
        $lowId = PriceListFactory::createWithPriority(1, ['name' => 'Low Priority']);
        $highId = PriceListFactory::createWithPriority(10, ['name' => 'High Priority']);
        $mediumId = PriceListFactory::createWithPriority(5, ['name' => 'Medium Priority']);

        $result = $this->repo->findAll(['page' => 1, 'limit' => 10]);
        
        // Should be ordered by priority descending
        $this->assertEquals($highId, $result[0]['id']);
        $this->assertEquals($mediumId, $result[1]['id']);
        $this->assertEquals($lowId, $result[2]['id']);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $pastDate = date('Y-m-d', strtotime('-10 days'));
        $futureDate = date('Y-m-d', strtotime('+10 days'));
        $today = date('Y-m-d');
        
        // Create lists with different date ranges
        $activeId = PriceListFactory::createWithDateRange($pastDate, $futureDate, ['name' => 'Active Range']);
        $expiredId = PriceListFactory::createWithDateRange($pastDate, $pastDate, ['name' => 'Expired Range']);
        $futureId = PriceListFactory::createWithDateRange($futureDate, $futureDate, ['name' => 'Future Range']);

        $result = $this->repo->applicablePriceLists(null, $today);
        $ids = array_column($result, 'id');
        
        // Only active range should be included
        $this->assertContains($activeId, $ids);
        $this->assertNotContains($expiredId, $ids);
        $this->assertNotContains($futureId, $ids);
    }
}
