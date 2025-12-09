<?php

namespace Tests\Services;

use App\Services\Orders\OrderService;
use App\Repositories\Orders\OrderRepository;
use App\Validators\OrderValidator;
use App\Validators\OrderCreateValidator;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\Pricing\PricingService;
use App\Services\Orders\OrderNumberGenerator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @agent-test: OrderService preview logic
 * @agent-pattern: Stubbed service test
 */
class OrderServiceTest extends CIUnitTestCase
{
    private OrderService $service;
    private InMemoryOrderRepository $repo;
    private OrderPricingStub $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryOrderRepository();
        $this->pricing = new OrderPricingStub();
        
        $validator = new OrderTestValidator();
        $createValidator = new OrderTestCreateValidator();
        $numberGen = new OrderTestNumberGenerator();

        $this->service = new OrderService(
            $this->repo,
            $validator,
            new PriceCalculatorService(),
            $this->pricing,
            $createValidator,
            $numberGen
        );
    }

    public function testPreviewReturnsCalculatedItems(): void
    {
        $payload = [
            'customer_id' => null,
            'order_date' => '2024-06-15',
            'items' => [
                ['product_id' => 1, 'variant_id' => null, 'quantity' => 2],
            ],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('items', $result['data']);
        $this->assertCount(1, $result['data']['items']);
    }

    public function testPreviewCalculatesSubtotalAndTotal(): void
    {
        $payload = [
            'customer_id' => null,
            'order_date' => '2024-06-15',
            'items' => [
                ['product_id' => 1, 'variant_id' => null, 'quantity' => 1],
            ],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $data = $result['data'];
        $this->assertArrayHasKey('subtotal', $data);
        $this->assertArrayHasKey('discount_total', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function testPreviewWithMultipleItems(): void
    {
        $payload = [
            'customer_id' => null,
            'order_date' => '2024-06-15',
            'items' => [
                ['product_id' => 1, 'variant_id' => null, 'quantity' => 2],
                ['product_id' => 2, 'variant_id' => null, 'quantity' => 3],
            ],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']['items']);
    }

    public function testPreviewWithEmptyItems(): void
    {
        $payload = [
            'customer_id' => null,
            'order_date' => '2024-06-15',
            'items' => [],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['data']['items']);
        $this->assertEquals(0, $result['data']['total']);
    }

    public function testPreviewItemsHavePriceInfo(): void
    {
        $payload = [
            'customer_id' => null,
            'order_date' => '2024-06-15',
            'items' => [
                ['product_id' => 1, 'variant_id' => null, 'quantity' => 1],
            ],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $item = $result['data']['items'][0];
        $this->assertArrayHasKey('base_price', $item);
        $this->assertArrayHasKey('final_price', $item);
        $this->assertArrayHasKey('line_total', $item);
    }

    public function testPreviewWithPriceListId(): void
    {
        $payload = [
            'customer_id' => null,
            'price_list_id' => 5,
            'order_date' => '2024-06-15',
            'items' => [
                ['product_id' => 1, 'variant_id' => null, 'quantity' => 1],
            ],
        ];

        $result = $this->service->preview($payload);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('applied_price_list_id', $result['data']);
    }
}

class OrderTestValidator extends OrderValidator
{
    public function validateOrder(array $data): array
    {
        return array_merge([
            'customer_id' => $data['customer_id'] ?? null,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'price_list_id' => $data['price_list_id'] ?? null,
            'order_date' => $data['order_date'] ?? date('Y-m-d'),
            'items' => $data['items'] ?? [],
        ], $data);
    }
}

class OrderTestCreateValidator extends OrderCreateValidator
{
    public function validate(array $data): array
    {
        return array_merge([
            'customer_id' => $data['customer_id'] ?? null,
            'order_date' => $data['order_date'] ?? date('Y-m-d'),
            'order_type' => $data['order_type'] ?? 'standard',
            'branch_id' => $data['branch_id'] ?? 1,
            'shipping_fee' => $data['shipping_fee'] ?? 0,
            'paid_amount' => $data['paid_amount'] ?? 0,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'shipping' => $data['shipping'] ?? ['name' => '', 'phone' => '', 'address' => '', 'ward' => '', 'district' => '', 'city' => ''],
            'notes' => $data['notes'] ?? '',
            'items' => $data['items'] ?? [],
            'payments' => $data['payments'] ?? [],
        ], $data);
    }
}

class OrderTestNumberGenerator extends OrderNumberGenerator
{
    public function generate(): string
    {
        return 'ORD-TEST-' . date('YmdHis');
    }
}

class InMemoryOrderRepository extends OrderRepository
{
    public array $storage = [];
    private int $nextId = 1;

    public function __construct() {}

    public function create(array $order, array $items = [], bool $useTransaction = true): array
    {
        $id = $this->nextId++;
        $order['id'] = $id;
        $order['items'] = $items;
        $this->storage[$id] = $order;
        return $order;
    }

    public function findById(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    public function updateFields(int $id, array $fields): bool
    {
        if (!isset($this->storage[$id])) return false;
        $this->storage[$id] = array_merge($this->storage[$id], $fields);
        return true;
    }
}

class OrderPricingStub extends PricingService
{
    public function __construct() {}

    public function getPrice(array $params): array
    {
        $quantity = $params['quantity'] ?? 1;
        $basePrice = 100;
        $finalPrice = 90;
        return [
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'line_total' => $finalPrice * $quantity,
            'applied_price_list_id' => $params['price_list_id'] ?? 1,
            'applied_price_list_name' => 'Default',
            'reason' => 'Standard discount',
        ];
    }
}
