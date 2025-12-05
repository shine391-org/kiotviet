<?php

namespace Tests\Services;

use App\Services\Products\ProductService;
use App\Services\PriceLists\PriceCalculatorService;
use App\Validators\ProductValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @agent-test: ProductService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class ProductServiceTest extends CIUnitTestCase
{
    private ProductService $service;
    private ProductServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ProductServiceFakeRepo();
        $validator = new class extends ProductValidator {
            public function validateListFilters(array $filters): array
            {
                return array_merge(['include_variants' => true, 'page' => 1, 'limit' => 20, 'price_list_id' => 9], $filters);
            }
            public function validateCreate(array $data): array { return $data; }
            public function validateUpdate(int $id, array $data): array { return $data; }
        };
        $pricing = new class extends PriceCalculatorService {
            public function getProductPriceByListId(int $priceListId, int $productId, ?int $variantId = null, float $quantity = 1): array
            {
                return [
                    'base_price' => 1000 + $productId + (int) ($variantId ?? 0),
                    'final_price' => 900 + $productId + (int) ($variantId ?? 0),
                    'applied_price_list_id' => $priceListId,
                    'applied_price_list_name' => 'List ' . $priceListId,
                    'price_list_type' => 'manual',
                ];
            }
        };
        $this->service = new ProductService($this->repo, $validator, $pricing);
    }

    public function testListReturnsVariantsAndPriceList(): void
    {
        $result = $this->service->list(['price_list_id' => 9, 'include_variants' => true]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $product = $result['data'][0];
        $this->assertSame(9, $product['applied_price_list_id']);
        $this->assertSame('manual', $product['price_list_type']);
        $this->assertNotEmpty($product['variants']);
        $this->assertSame(2, $product['variants'][0]['id']);
    }

    public function testGetReturnsCategoriesAndVariants(): void
    {
        $result = $this->service->get(1, true, true, 9);

        $this->assertTrue($result['success']);
        $data = $result['data'];
        $this->assertSame([10, 11], $data['category_ids']);
        $this->assertSame(9, $data['applied_price_list_id']);
        $this->assertSame(2, $data['variants'][0]['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->get(999, false, false);
    }

    public function testFindByCodeTrimsAndReturnsNullOnEmpty(): void
    {
        $this->assertNull($this->service->findByCode('   '));
        $this->assertSame(['id' => 1, 'code' => 'ABC'], $this->service->findByCode(' ABC '));
    }
}

class ProductServiceFakeRepo extends \App\Repositories\Products\ProductRepository
{
    public function __construct() {}

    public function findAll(array $filters = []): array
    {
        return [
            ['id' => 1, 'code' => 'P1', 'name' => 'Product 1', 'selling_price' => 1000],
        ];
    }

    public function count(array $filters = []): int
    {
        return 1;
    }

    public function categoryMap(array $productIds): array
    {
        return [1 => [10, 11]];
    }

    public function variantMap(array $productIds): array
    {
        return [1 => [['id' => 2, 'code' => 'V1']]];
    }

    public function findById(int $id): ?array
    {
        if ($id !== 1) {
            return null;
        }
        return ['id' => 1, 'code' => 'P1', 'name' => 'Product 1', 'selling_price' => 1000];
    }

    public function variantsByProduct(int $productId): array
    {
        return [['id' => 2, 'code' => 'V1']];
    }

    public function totalStock(int $productId): float
    {
        return 5.0;
    }

    public function findByCode(string $code): ?array
    {
        return $code === 'ABC' ? ['id' => 1, 'code' => 'ABC'] : null;
    }
}
