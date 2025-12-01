<?php

namespace Tests\Services;

use App\Repositories\Orders\OrderTemplateRepository;
use App\Repositories\Orders\OrderSubscriptionRepository;
use App\Repositories\PriceLists\CustomerPriceListRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\PriceLists\ProjectPriceListRepository;
use App\Repositories\Pricing\PriceHistoryRepository;
use App\Services\Orders\OrderService;
use App\Services\Orders\OrderTemplateService;
use App\Services\Orders\OrderSubscriptionService;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Pricing\PricingService;
use App\Services\PriceLists\PriceCalculatorService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\OrderTemplateSchemaTrait;
use Tests\Support\Database\ProductSchemaTrait;
use App\Repositories\Inventory\InventoryRepository;
use App\Validators\OrderValidator;
use App\Validators\OrderCreateValidator;

/**
 * @agent-test: OrderTemplateService
 * @agent-pattern: Service test with DevDatabaseTrait + schema traits
 */
class OrderTemplateServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use OrderTemplateSchemaTrait;
    use ProductSchemaTrait;

    private OrderTemplateService $service;
    private OrderSubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetOrderTemplateSchema();
        $this->resetSchema(); // product tables
        $this->seedBranch();
        $this->seedCustomer();
        $this->seedProduct(['id' => 1, 'code' => 'P1', 'name' => 'Prod 1', 'selling_price' => 50]);
        $this->seedPriceList(50);

        $priceListRepo = new PriceListRepository(null, $this->db);
        $priceListItemRepo = new PriceListItemRepository(null, $this->db);
        $pricingCalculator = new PriceCalculatorService($priceListRepo, $priceListItemRepo, $this->db);
        $pricingService = new PricingService(
            $pricingCalculator,
            null,
            new CustomerPriceListRepository(null, $this->db),
            new ProjectPriceListRepository(null, $this->db),
            new PriceHistoryRepository(),
            $this->db
        );
        $inventoryRepo = new InventoryRepository($this->db);
        $orderService = new OrderService(
            new \App\Repositories\Orders\OrderRepository(null, null, $this->db),
            new OrderValidator(),
            $pricingCalculator,
            $pricingService,
            new OrderCreateValidator(),
            null,
            null,
            null,
            new InventoryMovementLogger(),
            $inventoryRepo
        );
        $templateRepo = new OrderTemplateRepository(null, null, $this->db);
        $subscriptionRepo = new OrderSubscriptionRepository(null, $this->db);
        $this->service = new OrderTemplateService($templateRepo, null, $orderService);
        $this->subscriptionService = new OrderSubscriptionService($subscriptionRepo, null, $this->service);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_create_update_template(): void
    {
        $created = $this->service->createTemplate([
            'name' => 'Weekly order',
            'customer_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
            'notes' => 'Test note',
        ]);

        $this->assertTrue($created['success']);
        $this->assertNotEmpty($created['data']['id']);
        $this->assertCount(1, $created['data']['items']);

        $updated = $this->service->updateTemplate($created['data']['id'], [
            'name' => 'Updated name',
            'is_active' => false,
        ]);

        $this->assertSame('Updated name', $updated['data']['name']);
        $this->assertFalse((bool) $updated['data']['is_active']);
    }

    public function test_apply_template_creates_order_with_pricing(): void
    {
        $created = $this->service->createTemplate([
            'name' => 'Quick order',
            'customer_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        $applied = $this->service->applyTemplate($created['data']['id'], [
            'branch_id' => 1,
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'order_date' => '2025-11-29',
            'shipping_name' => 'Tester',
            'shipping_phone' => '0909',
            'shipping_address' => '123 Street',
        ]);

        $this->assertTrue($applied['success']);
        $order = $applied['data'];
        $this->assertSame('draft', $order['status']);
        $this->assertEquals(100.0, (float) $order['total']); // 2 * 50 selling price
        $this->assertCount(1, $order['items']);
    }

    public function test_apply_inactive_template_throws(): void
    {
        $created = $this->service->createTemplate([
            'name' => 'Inactive',
            'is_active' => false,
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->applyTemplate($created['data']['id'], [
            'branch_id' => 1,
            'payment_method' => 'CASH',
        ]);
    }

    public function test_run_due_subscription_creates_order_and_reschedules(): void
    {
        $template = $this->service->createTemplate([
            'name' => 'Subscription template',
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $sub = $this->subscriptionService->createSubscription([
            'template_id' => $template['data']['id'],
            'branch_id' => 1,
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'next_run_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'frequency_interval' => 7,
        ]);

        $result = $this->subscriptionService->runDueSubscriptions();
        $this->assertSame(1, $result['processed']);

        $updated = $this->subscriptionService->getSubscription($sub['data']['id']);
        $this->assertNotNull($updated['last_run_at']);
        $this->assertGreaterThan(strtotime($sub['data']['next_run_at']), strtotime($updated['next_run_at']));

        $orders = $this->db->table('orders')->get()->getResultArray();
        $this->assertCount(1, $orders);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedCustomer(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert([
            'id' => 1,
            'name' => 'Test Customer',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedProduct(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $payload = array_merge([
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Product',
            'selling_price' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);

        $this->db->table('products')->insert($payload);
        return (int) ($payload['id'] ?? $this->db->insertID());
    }

    private function seedPriceList(float $price): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'Base',
            'type' => 'custom',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
