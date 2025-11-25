<?php

namespace Tests\Services;

use App\Services\PriceLists\PriceFormulaService;
use App\Services\PriceLists\PriceListService;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Validators\PriceListValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;

/**
 * @agent-test: PriceListService unified MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + PriceListSchemaTrait (MySQL-only)
 */
class PriceListServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;

    private PriceListService $service;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use PriceListSchemaTrait for comprehensive schema
        $this->resetPriceListSchema();

        $repo = new PriceListRepository(null, $this->db);
        $items = new PriceListItemRepository(null, $this->db);
        $validator = new PriceListValidator();
        $formula = new PriceFormulaService();
        $products = new \App\Repositories\Products\ProductRepository(null, null, null, $this->db);
        $this->service = new PriceListService($repo, $items, $validator, $formula, $products);

        // seed product (DB prefix handles actual table name)
        $this->db->table('db_products')->insert([
            'code' => 'P1',
            'name' => 'Prod 1',
            'selling_price' => 200,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $productId = (int) $this->db->insertID();

        $this->productId = $productId;
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_auto_updates_nested_dependents()
    {
        $idA = $this->seedList('A', null, false, null);
        $idB = $this->seedList('B', $idA, true, 'base * 0.5');
        $idC = $this->seedList('C', $idB, true, 'base * 0.5');

        $this->seedItem($idA, $this->productId, null, 200);
        $this->seedItem($idB, $this->productId, null, 0);
        $this->seedItem($idC, $this->productId, null, 0);

        $this->assertSame(200.0, (float) $this->priceOf($idA, $this->productId));

        $updated = $this->service->triggerAutoUpdate($idA);

        $this->assertEqualsCanonicalizing([$idB, $idC], $updated);
        $this->assertSame(100.0, (float) $this->priceOf($idB, $this->productId));
        $this->assertSame(50.0, (float) $this->priceOf($idC, $this->productId));
    }

    /** @test */
    public function it_recalculates_single_list_from_base()
    {
        $idA = $this->seedList('A', null, false, null);
        $idB = $this->seedList('B', $idA, true, 'base * 0.5');

        $this->seedItem($idA, $this->productId, null, 200);
        $this->seedItem($idB, $this->productId, null, 0);

        $this->assertSame(200.0, (float) $this->priceOf($idA, $this->productId));

        $count = $this->service->recalculateItems($idB);

        $this->assertEquals(1, $count);
        $this->assertSame(100.0, (float) $this->priceOf($idB, $this->productId));
    }

    /** @test */
    public function it_skips_lists_with_auto_update_disabled()
    {
        $idA = $this->seedList('A', null, false, null);
        $idD = $this->seedList('D', $idA, false, 'base * 0.1');

        $this->seedItem($idA, $this->productId, null, 300);
        $this->seedItem($idD, $this->productId, null, 10);

        $updated = $this->service->triggerAutoUpdate($idA);

        $this->assertSame([], $updated);
        $this->assertSame(10.0, (float) $this->priceOf($idD, $this->productId));
    }

    private function seedList(string $name, ?int $baseId, bool $autoUpdate, ?string $formula): int
    {
        $this->db->table('db_price_lists')->insert([
            'name' => $name,
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'formula' => $formula,
            'base_price_list_id' => $baseId,
            'auto_update' => $autoUpdate ? 1 : 0,
            'rounding_rule' => 'none',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price): void
    {
        $this->db->table('db_price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function priceOf(int $listId, int $productId): ?float
    {
        $row = $this->db->table('db_price_list_items')
            ->where('price_list_id', $listId)
            ->where('product_id', $productId)
            ->get()->getRowArray();
        return $row ? (float) $row['price'] : null;
    }
}
