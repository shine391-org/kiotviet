<?php

namespace Tests\Services;

use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\PriceLists\PriceFormulaService;
use App\Services\PriceLists\PriceListService;
use App\Validators\PriceListValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Extended tests for PriceListService uncovered methods
 */
class PriceListServiceExtendedTest extends CIUnitTestCase
{
    private ExtendedPriceListRepository $repo;
    private ExtendedPriceListItemRepository $items;
    private ExtendedPriceListValidator $validator;
    private ExtendedPriceFormulaStub $formula;
    private ExtendedProductRepoStub $products;
    private PriceListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ExtendedPriceListRepository();
        $this->items = new ExtendedPriceListItemRepository();
        $this->validator = new ExtendedPriceListValidator();
        $this->formula = new ExtendedPriceFormulaStub();
        $this->products = new ExtendedProductRepoStub();
        $this->service = new PriceListService($this->repo, $this->items, $this->validator, $this->formula, $this->products);
    }

    public function testGetReturnsWithStatus(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1', 'is_active' => 1],
        ];

        $result = $this->service->get(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('status', $result['data']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Price list not found');
        $this->service->get(999);
    }

    public function testDeleteSuccess(): void
    {
        $this->repo->storage = [
            5 => ['id' => 5, 'name' => 'Custom PL', 'is_system' => 0],
        ];

        $result = $this->service->delete(5);

        $this->assertTrue($result['success']);
    }

    public function testDeleteThrowsForSystemPriceList(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'System', 'is_system' => 1],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Không thể xóa bảng giá mặc định');

        $this->service->delete(1);
    }

    public function testItemsReturnsListItems(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];
        $this->items->itemStorage = [
            1 => [
                ['product_id' => 10, 'price' => 100],
                ['product_id' => 11, 'price' => 200],
            ],
        ];

        $result = $this->service->items(1);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testUpsertItemsSuccess(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];

        $result = $this->service->upsertItems(1, [
            ['product_id' => 10, 'price' => 500],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('inserted', $result);
    }

    public function testAddItemsSuccess(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1', 'config' => null],
        ];

        $result = $this->service->addItems(1, [
            ['product_id' => 10, 'price' => 1000],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('inserted', $result);
    }

    public function testAddItemsWithFormulaConfig(): void
    {
        $formulaConfig = [
            'base' => 'cost',
            'operator' => '+',
            'value' => 10,
            'unit' => '%',
            'rounding' => 'none',
        ];
        $this->repo->storage = [
            2 => [
                'id' => 2,
                'name' => 'Formula PL',
                'config' => json_encode(['formula_config' => $formulaConfig]),
            ],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'cost_price' => 100000, 'selling_price' => 120000],
        ];

        $result = $this->service->addItems(2, [
            ['product_id' => 10, 'price' => 0],
        ]);

        $this->assertTrue($result['success']);
    }

    public function testRemoveItemSuccess(): void
    {
        $this->repo->storage = [
            5 => ['id' => 5, 'name' => 'PL5'],
        ];
        $this->items->itemStorage = [
            5 => [['product_id' => 10, 'price' => 100]],
        ];

        $result = $this->service->removeItem(5, 10);

        $this->assertTrue($result['success']);
    }

    public function testRemoveItemThrowsForSystemList(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'System'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove items from the general price list');

        $this->service->removeItem(1, 10);
    }

    public function testApplyFormulaWithPercentage(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];
        $this->items->itemStorage = [
            1 => [['product_id' => 10, 'price' => 100000]],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'selling_price' => 100000, 'cost_price' => 80000],
        ];

        $result = $this->service->applyFormula(1, [
            'base' => 'cost',
            'operator' => '+',
            'value' => 20,
            'unit' => '%',
            'rounding' => 'none',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_count']);
    }

    public function testApplyFormulaWithFixedAmount(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];
        $this->items->itemStorage = [
            1 => [['product_id' => 10, 'price' => 100000]],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'selling_price' => 100000],
        ];

        $result = $this->service->applyFormula(1, [
            'base' => 'current',
            'operator' => '+',
            'value' => 50000,
            'unit' => 'VND',
            'rounding' => 'thousand',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testApplyFormulaFromAnotherPriceList(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Base', 'is_system' => 0],
            2 => ['id' => 2, 'name' => 'Derived'],
        ];
        $this->items->itemStorage = [
            1 => [['product_id' => 10, 'price' => 90000]],
            2 => [['product_id' => 10, 'price' => 0]],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'selling_price' => 100000],
        ];

        $result = $this->service->applyFormula(2, [
            'base' => 1,
            'operator' => '+',
            'value' => 10,
            'unit' => '%',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testApplicable(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];

        $result = $this->service->applicable(null, date('Y-m-d'));

        $this->assertIsArray($result);
    }

    public function testExportItems(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Export PL'],
        ];
        $this->items->itemStorage = [
            1 => [
                ['product_id' => 10, 'price' => 100000, 'discount_percent' => 5, 'discount_amount' => 0],
            ],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'code' => 'PROD001', 'name' => 'Test Product'],
        ];

        $result = $this->service->exportItems(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('product_id', $result['csv']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('Export PL', $result['price_list']);
    }

    public function testImportItemsSuccess(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Import PL'],
        ];
        $this->products->productStorage = [
            10 => ['id' => 10, 'code' => 'PROD001', 'name' => 'Test'],
        ];

        $csv = "product_id,price\n10,150000";

        $result = $this->service->importItems(1, $csv);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function testImportItemsWithMissingColumn(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];

        $csv = "product_id\n10";

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required column: price');

        $this->service->importItems(1, $csv);
    }

    public function testImportItemsWithInvalidProductId(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];

        $csv = "product_id,price\n0,100000";

        $result = $this->service->importItems(1, $csv);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testImportItemsWithProductNotFound(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'PL1'],
        ];

        $csv = "product_id,price\n999,100000";

        $result = $this->service->importItems(1, $csv);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
    }

    public function testCreateWithFormulaConfig(): void
    {
        $data = [
            'name' => 'New PL',
            'formula_config' => [
                'base' => 'cost',
                'operator' => '+',
                'value' => 15,
                'unit' => '%',
            ],
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
    }

    public function testUpdateSuccess(): void
    {
        $this->repo->storage = [
            2 => ['id' => 2, 'name' => 'Old Name'],
        ];

        $result = $this->service->update(2, ['name' => 'New Name']);

        $this->assertTrue($result['success']);
    }

    public function testUpdateRejectsDuplicateName(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Existing Name'],
            2 => ['id' => 2, 'name' => 'Old Name'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price list name already exists');

        $this->service->update(2, ['name' => 'Existing Name']);
    }
}

class ExtendedPriceListRepository extends PriceListRepository
{
    public array $storage = [];
    private int $lastId = 100;

    public function __construct()
    {
    }

    public function findAll(array $filters): array
    {
        return array_values($this->storage);
    }

    public function count(array $filters): int
    {
        return count($this->storage);
    }

    public function findById(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        foreach ($this->storage as $id => $row) {
            if ($row['name'] === $name && ($excludeId === null || $excludeId !== $id)) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): array
    {
        $id = ++$this->lastId;
        $data['id'] = $id;
        $this->storage[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->storage[$id])) {
            $this->storage[$id] = array_merge($this->storage[$id], $data);
            return true;
        }
        return false;
    }

    public function delete(int $id): bool
    {
        unset($this->storage[$id]);
        return true;
    }

    public function dependentLists(int $priceListId): array
    {
        return array_values(array_filter($this->storage, fn($row) => ($row['base_price_list_id'] ?? null) === $priceListId));
    }

    public function applicablePriceLists(?int $groupId, string $date): array
    {
        return array_values($this->storage);
    }
}

class ExtendedPriceListItemRepository extends PriceListItemRepository
{
    public array $itemStorage = [];

    public function __construct()
    {
    }

    public function itemsByPriceList(int $priceListId): array
    {
        return $this->itemStorage[$priceListId] ?? [];
    }

    public function itemsRaw(int $priceListId): array
    {
        return $this->itemsByPriceList($priceListId);
    }

    public function replaceItems(int $priceListId, array $items): array
    {
        $this->itemStorage[$priceListId] = $items;
        return ['deleted' => true, 'inserted' => count($items)];
    }

    public function addItems(int $priceListId, array $items): array
    {
        if (!isset($this->itemStorage[$priceListId])) {
            $this->itemStorage[$priceListId] = [];
        }
        foreach ($items as $item) {
            $this->itemStorage[$priceListId][] = $item;
        }
        return ['inserted' => count($items)];
    }

    public function removeItem(int $priceListId, int $productId, ?int $variantId = null): bool
    {
        if (!isset($this->itemStorage[$priceListId])) {
            return false;
        }
        $before = count($this->itemStorage[$priceListId]);
        $this->itemStorage[$priceListId] = array_filter(
            $this->itemStorage[$priceListId],
            fn($item) => $item['product_id'] !== $productId
        );
        return count($this->itemStorage[$priceListId]) < $before;
    }

    public function findItem(int $priceListId, int $productId, ?int $variantId): ?array
    {
        $items = $this->itemStorage[$priceListId] ?? [];
        foreach ($items as $item) {
            if ($item['product_id'] === $productId) {
                return $item;
            }
        }
        return null;
    }
}

class ExtendedPriceListValidator extends PriceListValidator
{
    public function __construct()
    {
    }

    public function validateListFilters(array $filters): array
    {
        return array_merge(['page' => 1, 'limit' => 20], $filters);
    }

    public function validateCreate(array $data): array
    {
        return $data;
    }

    public function validateUpdate(array $data): array
    {
        return $data;
    }

    public function validateItems(array $items): array
    {
        return $items;
    }
}

class ExtendedPriceFormulaStub extends PriceFormulaService
{
    public function __construct()
    {
    }

    public function calculateFromFormula(string $formula, float $base): float
    {
        return $base * 1.2;
    }

    public function applyRounding(float $price, string $rule): float
    {
        if ($rule === 'thousand') {
            return round($price / 1000) * 1000;
        }
        return round($price, 2);
    }
}

class ExtendedProductRepoStub extends ProductRepository
{
    public array $productStorage = [];

    public function __construct()
    {
    }

    public function findById(int $id): ?array
    {
        return $this->productStorage[$id] ?? null;
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->productStorage);
    }
}
