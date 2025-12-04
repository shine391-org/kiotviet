<?php

namespace Tests\Services;

use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\PriceLists\PriceFormulaService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: PriceCalculatorService (stubbed)
 * @agent-pattern: Pricing engine with fake repositories
 */
class PriceCalculatorServiceTest extends CIUnitTestCase
{
    private FakePriceListRepository $priceLists;
    private FakePriceListItemRepository $items;
    private FakeProductRepository $products;
    private FakeVariantRepository $variants;
    private FakeFormulaService $formula;
    private PriceCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceLists = new FakePriceListRepository();
        $this->items = new FakePriceListItemRepository();
        $this->products = new FakeProductRepository();
        $this->variants = new FakeVariantRepository();
        $this->formula = new FakeFormulaService();
        $this->service = new PriceCalculatorService($this->priceLists, $this->items, $this->products, $this->variants, $this->formula);
    }

    public function testGetProductPriceAppliesDiscount(): void
    {
        $result = $this->service->getProductPrice(1, null, null, 2);

        $this->assertSame(100.0, $result['base_price']);
        $this->assertSame(90.0, $result['final_price']);
        $this->assertSame(180.0, $result['line_total']);
        $this->assertSame(1, $result['applied_price_list_id']);
    }

    public function testGetProductPriceByListIdReturnsInactive(): void
    {
        $this->priceLists->active = false;
        $result = $this->service->getProductPriceByListId(1, 1, null, 1);

        $this->assertNull($result['applied_price_list_id']);
        $this->assertSame(100.0, $result['base_price']);
        $this->assertSame(100.0, $result['final_price']);
    }

    public function testGetProductPriceByListIdUsesFormula(): void
    {
        $this->priceLists->listData['formula'] = 'BASE*0.5';
        $this->priceLists->listData['base_price_list_id'] = 2;
        $this->items->basePrice = 200;

        $result = $this->service->getProductPriceByListId(1, 1, 2, 3);

        $this->assertSame(50.0, $result['base_price']);
        $this->assertSame(100.0, $result['final_price']);
        $this->assertSame(300.0, $result['line_total']);
    }

    public function testGetProductPriceThrowsOnInvalidQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getProductPrice(1, null, null, 0);
    }

    public function testGetProductPriceThrowsWhenProductMissing(): void
    {
        $this->products->exists = false;
        $this->expectException(RuntimeException::class);
        $this->service->getProductPrice(1, null, null, 1);
    }
}

class FakePriceListRepository extends PriceListRepository
{
    public bool $active = true;
    public array $listData = [
        'id' => 1,
        'name' => 'Promo',
        'type' => 'discount',
        'is_active' => 1,
        'start_date' => null,
        'end_date' => null,
        'formula' => null,
        'base_price_list_id' => null,
        'rounding_rule' => null,
    ];

    public function __construct() {}

    public function applicablePriceLists(?int $customerGroupId, string $date): array
    {
        return [$this->listData];
    }

    public function findById(int $id): ?array
    {
        if (! $this->active) {
            return null;
        }
        return $this->listData;
    }
}

class FakePriceListItemRepository extends PriceListItemRepository
{
    public float $basePrice = 100;
    public function __construct() {}
    public function findItem(int $priceListId, int $productId, ?int $variantId): ?array
    {
        return [
            'price' => $this->basePrice,
            'discount_percent' => 10,
            'discount_amount' => 0,
        ];
    }
}

class FakeProductRepository extends ProductRepository
{
    public bool $exists = true;
    public function __construct() {}
    public function findById(int $id): ?array
    {
        if (! $this->exists) {
            return null;
        }
        return ['id' => 1, 'selling_price' => 100];
    }
}

class FakeVariantRepository extends ProductVariantRepository
{
    public function __construct() {}
    public function findById(int $id, bool $withDeleted = false): ?array
    {
        return ['id' => $id, 'product_id' => 1, 'price' => 50];
    }
}

class FakeFormulaService extends PriceFormulaService
{
    public function calculateFromFormula(string $formula, float $base): float
    {
        return $base * 0.5;
    }

    public function applyRounding(float $price, string $rule): float
    {
        return round($price, 2);
    }
}
