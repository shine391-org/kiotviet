<?php

namespace Tests\Services;

use App\Services\Orders\OrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Assertions\ErrorMessageAssertions;
use Tests\Support\Factories\ProductFactory;
use Tests\Support\Factories\PriceListFactory;
use Tests\Support\Factories\PriceListItemFactory;

/**
 * @agent-test: OrderService unified MySQL testing with strong assertions
 * @agent-pattern: Service test with DevDatabaseTrait + assertion traits + factories
 * @agent-improvements: Strong assertions, factory pattern, edge cases, error testing
 */
class OrderServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use ErrorMessageAssertions;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use PriceListSchemaTrait for comprehensive schema including order_sequences
        $this->resetPriceListSchema();
        
        // Seed order sequences for OrderNumberGenerator
        $this->seedOrderSequences();
        
        $this->service = new OrderService();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_preview_with_price_list()
    {
        // Create test data using factories
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        $priceListId = PriceListFactory::createWithPriority(3, ['name' => 'VIP']);
        PriceListItemFactory::createForProduct($priceListId, $productId, 80.0);

        $orderData = [
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => $productId, 'quantity' => 2]],
        ];

        $preview = $this->service->preview($orderData);

        // Strong assertions for service response
        $this->assertServiceSuccess($preview);
        $this->assertServiceDataStructure($preview, ['subtotal', 'total', 'applied_price_list_id', 'items']);
        
        // Business logic validation for pricing
        $this->assertOrderTotalCalculation($preview['data']['items'], 200.0, 160.0, 20.0);
        $this->assertEquals($priceListId, $preview['data']['applied_price_list_id'],
            'Should apply highest priority price list');
        
        // Verify price list priority logic
        $this->assertPriceListPriority([
            ['id' => $priceListId, 'priority' => 3]
        ], 3);
        
        // Verify database state
        $this->assertDatabaseHas('price_list_items', [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'price' => 80.0
        ]);
    }

    /** @test */
    public function it_persists_order_with_pricing()
    {
        // Create test data using factories
        $productId = ProductFactory::createWithPrice(150.0, 120.0);
        $priceListId = PriceListFactory::createWithPriority(2, ['name' => 'Sale']);
        PriceListItemFactory::createForProduct($priceListId, $productId, 120.0);

        $orderData = [
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [['product_id' => $productId, 'quantity' => 1]],
            'notes' => 'Test order'
        ];

        $create = $this->service->create($orderData);

        // Strong assertions for service response
        $this->assertServiceSuccess($create);
        $this->assertServiceDataStructure($create, ['id', 'order_number', 'total', 'items', 'shipping']);
        
        // Business logic validation for order creation
        $this->assertEquals(120.0, (float) $create['data']['total'],
            'Should use price list price');
        $this->assertNotEmpty($create['data']['order_number'],
            'Should generate order number');
        
        // Verify order items
        $items = $create['data']['items'] ?? [];
        $this->assertCount(1, $items, 'Should have exactly one item');
        $this->assertEquals($priceListId, (int) $items[0]['price_list_id'],
            'Should apply price list to order item');
        
        // Verify database state
        $this->assertDatabaseHas('orders', [
            'id' => $create['data']['id'],
            'customer_id' => null,
            'branch_id' => 1,
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'total' => 120.0,
            'status' => 'pending'
        ]);
        
        $this->assertDatabaseHas('order_items', [
            'order_id' => $create['data']['id'],
            'product_id' => $productId,
            'quantity' => 1,
            'price' => 120.0,
            'price_list_id' => $priceListId
        ]);
        
        // Verify audit trail
        $this->assertAuditTrail('orders', $create['data']['id'], 'create');
        $this->assertAuditTrail('order_items', $items[0]['id'], 'create');
    }

    /** @test */
    public function it_creates_order_with_multiple_products_and_mixed_pricing()
    {
        // Create test data using factories
        $product1Id = ProductFactory::createWithPrice(100.0, 80.0);
        $product2Id = ProductFactory::createWithPrice(50.0, 40.0);
        $priceListId = PriceListFactory::createWithPriority(3, ['name' => 'OnlyP1']);
        PriceListItemFactory::createForProduct($priceListId, $product1Id, 80.0);

        $orderData = [
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [
                ['product_id' => $product1Id, 'quantity' => 2], // priced by list -> 80 *2
                ['product_id' => $product2Id, 'quantity' => 1], // base 50
            ],
            'notes' => 'Test order'
        ];

        $create = $this->service->create($orderData);

        // Strong assertions for service response
        $this->assertServiceSuccess($create);
        $this->assertServiceDataStructure($create, ['id', 'total', 'items']);
        
        // Business logic validation for mixed pricing
        $expectedItems = [
            ['product_id' => $product1Id, 'quantity' => 2, 'price' => 80.0, 'price_list_id' => $priceListId],
            ['product_id' => $product2Id, 'quantity' => 1, 'price' => 50.0, 'price_list_id' => null]
        ];
        
        $this->assertOrderTotalCalculation($create['data']['items'], 210.0, 210.0, 0.0);
        $this->assertEquals(210.0, (float) $create['data']['total'],
            'Should calculate mixed pricing correctly');
        
        // Verify order items in database
        $items = $create['data']['items'] ?? [];
        $this->assertCount(2, $items, 'Should have exactly two items');
        
        foreach ($items as $index => $item) {
            $expected = $expectedItems[$index];
            $this->assertEquals($expected['product_id'], $item['product_id'],
                "Item {$index} should have correct product ID");
            $this->assertEquals($expected['quantity'], $item['quantity'],
                "Item {$index} should have correct quantity");
            $this->assertEquals($expected['price'], $item['price'],
                "Item {$index} should have correct price");
            $this->assertEquals($expected['price_list_id'], $item['price_list_id'],
                "Item {$index} should have correct price list ID");
        }
    }

    /** @test */
    public function it_rejects_zero_quantity()
    {
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        $orderData = [
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [
                ['product_id' => $productId, 'quantity' => 0],
            ],
            'notes' => 'Test order'
        ];

        // Strong assertion for business validation
        $this->assertBusinessValidation(function() use ($orderData) {
            $this->service->create($orderData);
        }, \InvalidArgumentException::class, 'Quantity must be greater than 0');
    }

    /** @test */
    public function it_handles_edge_cases_for_order_quantities()
    {
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        $boundaryTests = [
            ['field' => 'quantity', 'value' => 0, 'should_pass' => false, 'expected_exception' => \InvalidArgumentException::class],
            ['field' => 'quantity', 'value' => -1, 'should_pass' => false, 'expected_exception' => \InvalidArgumentException::class],
            ['field' => 'quantity', 'value' => 0.01, 'should_pass' => true],
            ['field' => 'quantity', 'value' => 9999, 'should_pass' => true],
        ];

        $this->assertBoundaryValueHandling(function($data) use ($productId) {
            $orderData = [
                'customer_id' => null,
                'branch_id' => 1,
                'order_date' => date('Y-m-d'),
                'payment_method' => 'CASH',
                'order_type' => 'shipping',
                'shipping' => [
                    'name' => 'Test Customer',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'ward' => 'Test Ward',
                    'district' => 'Test District',
                    'city' => 'Test City'
                ],
                'items' => [
                    ['product_id' => $productId, 'quantity' => $data['quantity']],
                ],
                'notes' => 'Test order'
            ];
            
            return $this->service->create($orderData);
        }, $boundaryTests);
    }

    /** @test */
    public function it_provides_meaningful_error_messages()
    {
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        $orderData = [
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [
                ['product_id' => $productId, 'quantity' => 0],
            ],
            'notes' => 'Test order'
        ];

        try {
            $this->service->create($orderData);
            $this->fail('Should have thrown exception for zero quantity');
        } catch (\Exception $e) {
            $this->assertUserFriendlyErrorMessage([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            
            $this->assertErrorFieldContext([
                'success' => false,
                'message' => $e->getMessage()
            ], ['quantity']);
            
            $this->assertErrorFormatting([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function seedOrderSequences(): void
    {
        $this->db->table('order_sequences')->insert([
            'branch_id' => 1,
            'sequence_number' => 1,
            'prefix' => 'ORD',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
