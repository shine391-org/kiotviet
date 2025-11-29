<?php

namespace Tests\Services;

use App\Services\Pricing\PricingService;
use App\Services\Pricing\PricingRuleService;
use App\Repositories\Pricing\PricingRuleRepository;
use App\Repositories\Pricing\PriceHistoryRepository;
use App\Repositories\PriceLists\CustomerPriceListRepository;
use App\Repositories\PriceLists\ProjectPriceListRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Validators\PricingRuleValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: PricingService @agent-pattern: Service test with DevDatabaseTrait */
class PricingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PricingService $service;
    private PricingRuleService $ruleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $db = $this->db;
        $this->ruleService = new PricingRuleService(new PricingRuleRepository(null, $db), new PricingRuleValidator());
        $baseCalc = new PriceCalculatorService(new PriceListRepository(null, $db), new PriceListItemRepository(null, $db), $db);
        $this->service = new PricingService(
            $baseCalc,
            $this->ruleService,
            new CustomerPriceListRepository(null, $db),
            new ProjectPriceListRepository(null, $db),
            new PriceHistoryRepository(),
            $db
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_customer_price_list()
    {
        $price = $this->service->getPrice([
            'product_id' => 1,
            'customer_id' => 1,
            'quantity' => 1,
            'order_date' => '2025-11-29',
        ]);
        $this->assertEquals(80.0, $price['final_price']);
        $this->assertEquals('customer_price_list', $price['reason']['source']);
    }

    /** @test */
    public function it_applies_pricing_rule_over_base()
    {
        $this->ruleService->create([
            'name' => 'Rule discount',
            'condition_type' => 'product',
            'product_id' => 1,
            'discount_percent' => 10,
            'priority' => 10,
        ]);

        $base = $this->service->getPrice([
            'product_id' => 1,
            'quantity' => 1,
        ]);
        $this->assertGreaterThan(0, $base['final_price']);

        $price = $this->service->getPrice([
            'product_id' => 1,
            'quantity' => 2,
        ]);
        $this->assertEqualsWithDelta(72.0, $price['final_price'], 0.001); // base from list 80 * 0.9
        $this->assertEquals('pricing_rule', $price['reason']['source']);

        $historyRow = $this->db->table('price_history')->get()->getRowArray();
        $this->assertNotNull($historyRow);
        $this->assertEquals(80.0, (float) $historyRow['old_price']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Product 1',
            'selling_price' => 100,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'VIP',
            'is_active' => 1,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'price' => 80,
        ]);
        $this->db->table('customer_price_lists')->insert([
            'customer_id' => 1,
            'price_list_id' => 1,
            'is_active' => 1,
        ]);

        $row = $this->db->table('products')->get()->getRowArray();
        $this->assertEquals(100.0, (float) $row['selling_price']);
        $this->assertEquals(1, $this->db->table('price_list_items')->countAllResults());
        $this->assertEquals(1, $this->db->table('customer_price_lists')->countAllResults());
    }
}
