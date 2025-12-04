<?php

namespace Tests\Services;

use App\Services\PriceLists\PriceFormulaService;
use App\Services\PriceLists\PriceListService;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Validators\PriceListValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Assertions\ErrorMessageAssertions;
use Tests\Support\Factories\ProductFactory;
use Tests\Support\Factories\PriceListFactory;
use Tests\Support\Factories\PriceListItemFactory;

/**
 * @agent-test: PriceListService unified MySQL testing with strong assertions
 * @agent-pattern: Service test with DevDatabaseTrait + assertion traits + factories
 * @agent-improvements: Strong assertions, factory pattern, edge cases, error testing
 */
class PriceListServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use ErrorMessageAssertions;

    private PriceListService $service;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use PriceListSchemaTrait for comprehensive schema
        $this->resetCompleteSchema();

        $repo = new PriceListRepository(null, $this->db);
        $items = new PriceListItemRepository(null, $this->db);
        $validator = new PriceListValidator();
        $formula = new PriceFormulaService();
        $products = new \App\Repositories\Products\ProductRepository(null, null, null, $this->db);
        $this->service = new PriceListService($repo, $items, $validator, $formula, $products);

        // Create product using factory instead of hardcoded seed
        $this->productId = ProductFactory::createWithPrice(200.0, 150.0);
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_auto_updates_nested_dependents()
    {
        // Create price lists using factories
        $idA = PriceListFactory::createActive(['name' => 'Base List A']);
        $idB = PriceListFactory::createWithFormula('base * 0.5', $idA, true, ['name' => 'List B']);
        $idC = PriceListFactory::createWithFormula('base * 0.5', $idB, true, ['name' => 'List C']);

        // Create price list items using factories
        PriceListItemFactory::createForProduct($idA, $this->productId, 200.0);
        PriceListItemFactory::createForProduct($idB, $this->productId, 0.0);
        PriceListItemFactory::createForProduct($idC, $this->productId, 0.0);

        // Verify initial state with strong assertions
        $this->assertDatabaseHas('price_list_items', [
            'price_list_id' => $idA,
            'product_id' => $this->productId,
            'price' => 200.0
        ]);
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idA, $this->productId), 'price', 200.0);

        $updated = $this->service->triggerAutoUpdate($idA);

        // Strong assertions for business logic
        $this->assertIsArray($updated, 'Auto update should return array of updated list IDs');
        $this->assertEqualsCanonicalizing([$idB, $idC], $updated,
            'Should update both dependent lists');
        
        // Verify price calculations with business logic assertions
        $this->assertPriceCalculation(200.0, 100.0, 0.5, 0.01,
            'List B should have 50% discount from base');
        $this->assertPriceCalculation(100.0, 50.0, 0.5, 0.01,
            'List C should have 50% discount from List B');
        
        // Verify database state with strong assertions
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idB, $this->productId), 'price', 100.0);
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idC, $this->productId), 'price', 50.0);
        
        // Verify audit trail
        $this->assertAuditTrail('price_list_items',
            $this->getItemId($idB, $this->productId), 'update');
        $this->assertAuditTrail('price_list_items',
            $this->getItemId($idC, $this->productId), 'update');
    }

    /** @test */
    public function it_recalculates_single_list_from_base()
    {
        // Create price lists using factories
        $idA = PriceListFactory::createActive(['name' => 'Base List A']);
        $idB = PriceListFactory::createWithFormula('base * 0.5', $idA, true, ['name' => 'List B']);

        // Create price list items using factories
        PriceListItemFactory::createForProduct($idA, $this->productId, 200.0);
        PriceListItemFactory::createForProduct($idB, $this->productId, 0.0);

        // Verify initial state
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idA, $this->productId), 'price', 200.0);

        $count = $this->service->recalculateItems($idB);

        // Strong assertions for business logic
        $this->assertEquals(1, $count, 'Should recalculate exactly 1 item');
        $this->assertPriceCalculation(200.0, 100.0, 0.5, 0.01,
            'Should apply 50% discount formula');
        
        // Verify database state
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idB, $this->productId), 'price', 100.0);
        
        // Verify only target list was updated
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idA, $this->productId), 'price', 200.0);
    }

    /** @test */
    public function it_skips_lists_with_auto_update_disabled()
    {
        // Create price lists using factories
        $idA = PriceListFactory::createActive(['name' => 'Base List A']);
        $idD = PriceListFactory::createWithFormula('base * 0.1', $idA, false, ['name' => 'List D']);

        // Create price list items using factories
        PriceListItemFactory::createForProduct($idA, $this->productId, 300.0);
        PriceListItemFactory::createForProduct($idD, $this->productId, 10.0);

        $updated = $this->service->triggerAutoUpdate($idA);

        // Strong assertions for business logic
        $this->assertIsArray($updated, 'Auto update should return array');
        $this->assertEmpty($updated, 'Should not update lists with auto_update disabled');
        
        // Verify database state unchanged
        $this->assertDatabaseDecimalValue('price_list_items',
            $this->getItemId($idD, $this->productId), 'price', 10.0);
        
        // Verify price list dependency
        $this->assertPriceListDependency($idA, $idD, false, 'base * 0.1');
    }

    /** @test */
    public function it_handles_edge_cases_for_price_calculations()
    {
        // Test boundary values for price calculations
        $edgeCases = [
            ['base_price' => 0.01, 'formula' => 'base * 2', 'expected' => 0.02],
            ['base_price' => 999999.99, 'formula' => 'base * 0.01', 'expected' => 9999.9999],
            ['base_price' => 100.0, 'formula' => 'base + 50', 'expected' => 150.0],
            ['base_price' => 100.0, 'formula' => 'base - 25', 'expected' => 75.0],
        ];

        foreach ($edgeCases as $case) {
            $baseListId = PriceListFactory::createActive();
            $dependentListId = PriceListFactory::createWithFormula($case['formula'], $baseListId, true);
            
            PriceListItemFactory::createForProduct($baseListId, $this->productId, $case['base_price']);
            PriceListItemFactory::createForProduct($dependentListId, $this->productId, 0.0);
            
            $this->service->recalculateItems($dependentListId);
            
            // Verify boundary value handling
            $this->assertDatabaseDecimalValue('price_list_items',
                $this->getItemId($dependentListId, $this->productId), 'price', $case['expected'], 0.0001,
                "Formula '{$case['formula']}' should calculate correctly for base price {$case['base_price']}");
        }
    }

    /** @test */
    public function it_validates_formula_syntax()
    {
        $invalidFormulas = [
            'invalid syntax',
            'base *',
            '* base',
            'base / 0',
            'base +',
            'base -',
        ];

        foreach ($invalidFormulas as $formula) {
            $baseListId = PriceListFactory::createActive();
            
            // Test formula validation
            $this->assertBusinessValidation(function() use ($baseListId, $formula) {
                PriceListFactory::createWithFormula($formula, $baseListId, true);
            }, \InvalidArgumentException::class, null,
                "Invalid formula '{$formula}' should throw exception");
        }
    }

    /** @test */
    public function it_handles_null_and_empty_values()
    {
        $this->assertNullValueHandling(function($data) {
            $baseListId = PriceListFactory::createActive();
            return PriceListFactory::createWithFormula($data['formula'] ?? 'base * 0.5', $baseListId, true);
        }, ['formula'], \InvalidArgumentException::class);

        $this->assertEmptyValueHandling(function($data) {
            $baseListId = PriceListFactory::createActive();
            return PriceListFactory::createWithFormula($data['formula'] ?? 'base * 0.5', $baseListId, true);
        }, ['formula'], \InvalidArgumentException::class);
    }

    /** @test */
    public function it_provides_meaningful_error_messages()
    {
        $baseListId = PriceListFactory::createActive();
        
        // Test error message quality
        try {
            PriceListFactory::createWithFormula('invalid syntax', $baseListId, true);
            $this->fail('Should have thrown exception for invalid formula');
        } catch (\Exception $e) {
            $this->assertUserFriendlyErrorMessage([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            
            $this->assertErrorFieldContext([
                'success' => false,
                'message' => $e->getMessage()
            ], ['formula']);
            
            $this->assertErrorFormatting([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper method to get price list item ID
     */
    private function getItemId(int $priceListId, int $productId): int
    {
        $item = $this->db->table('price_list_items')
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->get()->getRowArray();
        
        return (int) $item['id'];
    }

    /**
     * Helper method to get price for a product in a price list
     */
    private function getPrice(int $priceListId, int $productId): float
    {
        $item = $this->db->table('price_list_items')
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->get()->getRowArray();
        
        return (float) $item['price'];
    }
}
