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

/**
 * @agent-test: PriceListService
 * @agent-pattern: Service orchestration with fakes
 */
class PriceListServiceTest extends CIUnitTestCase
{
    private InMemoryPriceListRepository $repo;
    private InMemoryPriceListItemRepository $items;
    private TestPriceListValidator $validator;
    private PriceListFormulaStub $formula;
    private PriceListProductRepoStub $products;
    private PriceListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryPriceListRepository();
        $this->items = new InMemoryPriceListItemRepository();
        $this->validator = new TestPriceListValidator();
        $this->formula = new PriceListFormulaStub();
        $this->products = new PriceListProductRepoStub();
        $this->service = new PriceListService($this->repo, $this->items, $this->validator, $this->formula, $this->products);
    }

    public function testListAddsStatusAndPagination(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Active', 'is_active' => 1, 'start_date' => date('Y-m-d', strtotime('-1 day')), 'end_date' => null],
            2 => ['id' => 2, 'name' => 'Upcoming', 'is_active' => 1, 'start_date' => date('Y-m-d', strtotime('+2 days')), 'end_date' => null],
            3 => ['id' => 3, 'name' => 'Expired', 'is_active' => 1, 'start_date' => date('Y-m-d', strtotime('-5 days')), 'end_date' => date('Y-m-d', strtotime('-1 day'))],
            4 => ['id' => 4, 'name' => 'Inactive', 'is_active' => 0],
        ];

        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $statuses = array_column($result['data'], 'status', 'name');
        $this->assertSame('active', $statuses['Active']);
        $this->assertSame('upcoming', $statuses['Upcoming']);
        $this->assertSame('expired', $statuses['Expired']);
        $this->assertSame('inactive', $statuses['Inactive']);
        $this->assertSame(4, $result['pagination']['total']);
    }

    public function testCreateRejectsDuplicateName(): void
    {
        $this->repo->nameExistsFlag = true;
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['name' => 'Dup']);
    }

    public function testUpdateGuardsAgainstCircularBaseReference(): void
    {
        $this->repo->storage = [
            2 => ['id' => 2, 'name' => 'PL2', 'base_price_list_id' => 3],
            3 => ['id' => 3, 'name' => 'PL3', 'base_price_list_id' => 2],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->service->update(2, ['base_price_list_id' => 3]);
    }

    public function testRecalculateItemsUsesFormulaAndBasePrice(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Base'],
            2 => ['id' => 2, 'name' => 'Derived', 'formula' => 'BASE*1.5', 'base_price_list_id' => 1, 'rounding_rule' => 'none'],
        ];
        $this->items->replaceItems(1, [
            ['product_id' => 10, 'variant_id' => null, 'price' => 120],
        ]);
        $this->items->replaceItems(2, [
            ['product_id' => 10, 'variant_id' => null, 'price' => 0],
        ]);

        $count = $this->service->recalculateItems(2);

        $this->assertSame(1, $count);
        $this->assertSame(180.0, $this->items->itemsByPriceList(2)[0]['price']);
    }

    public function testTriggerAutoUpdateRecalculatesDependents(): void
    {
        $this->repo->storage = [
            1 => ['id' => 1, 'name' => 'Base'],
            2 => ['id' => 2, 'name' => 'Auto child', 'auto_update' => true, 'base_price_list_id' => 1],
            3 => ['id' => 3, 'name' => 'Manual child', 'auto_update' => false, 'base_price_list_id' => 1],
        ];
        $this->items->replaceItems(2, [['product_id' => 11, 'variant_id' => null, 'price' => 50]]);
        $this->items->replaceItems(3, [['product_id' => 12, 'variant_id' => null, 'price' => 60]]);
        $initialCalls = $this->items->replaceCalls;

        $updated = $this->service->triggerAutoUpdate(1);

        $this->assertSame([2], $updated);
        $this->assertSame($initialCalls[2] + 1, $this->items->replaceCalls[2]);
        $this->assertSame($initialCalls[3], $this->items->replaceCalls[3]);
    }
}

class InMemoryPriceListRepository extends PriceListRepository
{
    public array $storage = [];
    public bool $nameExistsFlag = false;
    private int $lastId = 0;

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
        if ($this->nameExistsFlag) {
            return true;
        }
        foreach ($this->storage as $id => $row) {
            if ($row['name'] === $name && (! $excludeId || $excludeId !== $id)) {
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
        $this->storage[$id] = array_merge($this->storage[$id] ?? [], $data);
        return true;
    }

    public function delete(int $id): bool
    {
        unset($this->storage[$id]);
        return true;
    }

    public function dependentLists(int $priceListId): array
    {
        return array_values(array_filter($this->storage, static function ($row) use ($priceListId) {
            return ($row['base_price_list_id'] ?? null) === $priceListId;
        }));
    }

    public function applicablePriceLists(?int $groupId, string $date): array
    {
        return array_values($this->storage);
    }
}

class InMemoryPriceListItemRepository extends PriceListItemRepository
{
    public array $itemStorage = [];
    public array $replaceCalls = [];

    public function __construct()
    {
    }

    public function itemsByPriceList(int $priceListId): array
    {
        return array_values($this->itemStorage[$priceListId] ?? []);
    }

    public function itemsRaw(int $priceListId): array
    {
        return $this->itemsByPriceList($priceListId);
    }

    public function replaceItems(int $priceListId, array $items): array
    {
        $this->replaceCalls[$priceListId] = ($this->replaceCalls[$priceListId] ?? 0) + 1;
        $this->itemStorage[$priceListId] = $items;
        return ['deleted' => true, 'inserted' => count($items)];
    }

    public function findItem(int $priceListId, int $productId, ?int $variantId): ?array
    {
        $items = $this->itemStorage[$priceListId] ?? [];
        foreach ($items as $item) {
            if ($item['product_id'] === $productId && ($item['variant_id'] ?? null) === $variantId) {
                return $item;
            }
        }
        foreach ($items as $item) {
            if ($item['product_id'] === $productId && ($item['variant_id'] ?? null) === null) {
                return $item;
            }
        }
        return null;
    }
}

class TestPriceListValidator extends PriceListValidator
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

class PriceListFormulaStub extends PriceFormulaService
{
    public function __construct()
    {
    }

    public function calculateFromFormula(string $formula, float $base): float
    {
        return $base * 1.5;
    }

    public function applyRounding(float $price, string $rule): float
    {
        return round($price, 2);
    }
}

class PriceListProductRepoStub extends ProductRepository
{
    public function __construct()
    {
    }

    public function findById(int $id): ?array
    {
        return ['id' => $id, 'selling_price' => 75];
    }
}
