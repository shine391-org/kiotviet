<?php

namespace Tests\Integration\Orders;

use App\Services\Orders\OrderService;
use App\Services\PriceLists\PriceCalculatorService;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\Products\ProductRepository;
use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Validators\OrderValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class FakeProductRepository extends ProductRepository
{
    private array $map = [];
    public function __construct() { $this->products = new \App\Models\ProductModel(); }
    public function addProduct(int $id, float $price): void
    {
        $this->map[$id] = ['id' => $id, 'selling_price' => $price];
    }
    public function findById(int $id): ?array
    {
        return $this->map[$id] ?? null;
    }
}
class OrderPriceListTest extends CIUnitTestCase
{

    protected $db;
    protected OrderService $service;
    protected FakeProductRepository $fakeProducts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');

        $priceListRepo = new PriceListRepository(null, $this->db);
        $priceListItemRepo = new PriceListItemRepository(null, $this->db);
        $this->fakeProducts = new FakeProductRepository();
        $variantRepo = new ProductVariantRepository(null, null, $this->db);
        $pricing = new PriceCalculatorService($priceListRepo, $priceListItemRepo, $this->fakeProducts, $variantRepo);

        $orderRepo = new OrderRepository(null, null, $this->db);
        $this->service = new OrderService($orderRepo, new OrderValidator(), $pricing);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_create_order_with_price_list_applies_discount_percentage(): void
    {
        $productId = $this->seedProduct(100000);
        $pl = $this->seedPriceList(['priority' => 5, 'apply_to_groups' => []]);
        $this->seedPriceListItem($pl, $productId, price: 0, discountPercent: 10, discountAmount: 0);

        $payload = [
            'customer_id' => null,
            'customer_group_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [
                ['product_id' => $productId, 'quantity' => 1],
            ],
        ];

        $lists = (new PriceListRepository(null, $this->db))->applicablePriceLists(1, $payload['order_date']);
        $this->assertNotEmpty($lists, 'No applicable price list');

        $res = $this->service->preview($payload);
        $this->assertEquals(90000.0, (float) $res['data']['items'][0]['final_price']);
        $this->assertEquals(90000.0, (float) $res['data']['total']);
    }

    public function test_create_order_with_price_list_applies_discount_fixed(): void
    {
        $productId = $this->seedProduct(100000);
        $pl = $this->seedPriceList(['priority' => 5, 'apply_to_groups' => []]);
        $this->seedPriceListItem($pl, $productId, price: 0, discountPercent: 0, discountAmount: 50000);

        $payload = [
            'customer_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [
                ['product_id' => $productId, 'quantity' => 1],
            ],
        ];

        $res = $this->service->preview($payload);
        $this->assertEquals(50000.0, (float) $res['data']['items'][0]['final_price']);
        $this->assertEquals(50000.0, (float) $res['data']['total']);

        // Edge: discount overflow -> price floored at 0
        $this->db->table('price_list_items')->truncate();
        $this->seedPriceListItem($pl, $productId, price: 0, discountPercent: 0, discountAmount: 200000);
        $res = $this->service->preview($payload);
        $this->assertEquals(0.0, (float) $res['data']['items'][0]['final_price']);
    }

    public function test_change_price_list_on_existing_order_recalculates(): void
    {
        $productId = $this->seedProduct(100000);
        $listA = $this->seedPriceList(['name' => 'A', 'priority' => 3, 'apply_to_groups' => []]);
        $listB = $this->seedPriceList(['name' => 'B', 'priority' => 1, 'apply_to_groups' => []]);
        $this->seedPriceListItem($listA, $productId, price: 80000);
        $this->seedPriceListItem($listB, $productId, price: 60000);

        $payload = [
            'customer_group_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [ ['product_id' => $productId, 'quantity' => 1] ],
        ];

        $first = $this->service->preview($payload);
        $this->assertEquals(80000.0, (float) $first['data']['items'][0]['final_price']);

        // nâng priority của B để áp dụng
        $this->db->table('price_lists')->where('id', $listB)->update(['priority' => 9]);
        $secondPreview = $this->service->preview($payload);
        $this->assertEquals($listB, $secondPreview['data']['applied_price_list_id']);
        $this->assertEquals(60000.0, $secondPreview['data']['items'][0]['final_price']);
    }

    public function test_priority_conflict_when_multiple_price_lists_active(): void
    {
        $productId = $this->seedProduct(120000);
        $high = $this->seedPriceList(['name' => 'High', 'priority' => 10, 'apply_to_groups' => []]);
        $low = $this->seedPriceList(['name' => 'Low', 'priority' => 2, 'apply_to_groups' => []]);
        $this->seedPriceListItem($high, $productId, price: 70000);
        $this->seedPriceListItem($low, $productId, price: 90000);

        $payload = [
            'customer_group_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [ ['product_id' => $productId, 'quantity' => 1] ],
        ];

        $preview = $this->service->preview($payload);
        $this->assertEquals($high, $preview['data']['applied_price_list_id']);
        $this->assertEquals(70000.0, $preview['data']['items'][0]['final_price']);
    }

    public function test_price_list_with_date_range_only_applies_within_period(): void
    {
        $productId = $this->seedProduct(50000);
        $pl = $this->seedPriceList([
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-31',
            'priority' => 5,
            'apply_to_groups' => [],
        ]);
        $this->seedPriceListItem($pl, $productId, price: 30000);

        $payloadIn = [
            'order_date' => '2025-12-15',
            'items' => [ ['product_id' => $productId, 'quantity' => 1] ],
        ];
        $payloadOut = [
            'order_date' => '2026-01-05',
            'items' => [ ['product_id' => $productId, 'quantity' => 1] ],
        ];

        $in = $this->service->preview($payloadIn);
        $this->assertEquals($pl, $in['data']['applied_price_list_id']);
        $this->assertEquals(30000.0, $in['data']['items'][0]['final_price']);

        $out = $this->service->preview($payloadOut);
        $this->assertNull($out['data']['applied_price_list_id']);
        $this->assertEquals(50000.0, $out['data']['items'][0]['final_price']);
    }

    private function seedProduct(float $price): int
    {
        $row = [
            'code' => 'P' . random_int(100, 999),
            'name' => 'Product',
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ];
        $this->db->table('products')->insert($row);
        $id = (int) $this->db->insertID();
        $this->fakeProducts->addProduct($id, $price);
        return $id;
    }

    private function seedPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'PL' . random_int(100, 999),
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => null,
            'apply_to_groups' => $data['apply_to_groups'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        $payload['apply_to_groups'] = json_encode($payload['apply_to_groups'] ?? []);
        $this->db->table('price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedPriceListItem(int $priceListId, int $productId, float $price = 0, float $discountPercent = 0, float $discountAmount = 0): void
    {
        $this->db->table('price_list_items')->insert([
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => null,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
