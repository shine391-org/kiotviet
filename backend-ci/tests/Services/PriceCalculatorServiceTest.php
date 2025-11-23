<?php

namespace Tests\Services;

use App\Services\PriceLists\PriceCalculatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: PriceCalculatorService tests @agent-pattern: Pricing engine test */
class PriceCalculatorServiceTest extends CIUnitTestCase
{
    use PriceListSchemaTrait;

    private PriceCalculatorService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->service = new PriceCalculatorService();
    }

    /** @test */
    public function it_picks_highest_priority_list()
    {
        $pid = $this->seedProduct(100);
        $low = $this->seedPriceList(['name' => 'Low', 'priority' => 1]);
        $high = $this->seedPriceList(['name' => 'High', 'priority' => 5]);
        $this->seedItem($low, $pid, null, 90);
        $this->seedItem($high, $pid, null, 70);

        $price = $this->service->getProductPrice($pid, null, null);
        $this->assertEquals(70.0, $price['final_price']);
        $this->assertEquals($high, $price['applied_price_list_id']);
    }

    /** @test */
    public function it_falls_back_to_base_when_no_list_applies()
    {
        $pid = $this->seedProduct(120);
        $price = $this->service->getProductPrice($pid, null, null);
        $this->assertSame(120.0, $price['final_price']);
        $this->assertNull($price['applied_price_list_id']);
    }

    /** @test */
    public function it_ignores_expired_price_list()
    {
        $pid = $this->seedProduct(150);
        $expired = $this->seedPriceList(['name' => 'Expired', 'end_date' => date('Y-m-d', strtotime('-1 day'))]);
        $this->seedItem($expired, $pid, null, 50);

        $price = $this->service->getProductPrice($pid, null, null);
        $this->assertSame(150.0, $price['final_price']); // base price used
        $this->assertNull($price['applied_price_list_id']);
    }

    /** @test */
    public function it_returns_zero_when_discounts_overflow()
    {
        $pid = $this->seedProduct(100);
        $list = $this->seedPriceList(['name' => 'Overflow']);
        $this->seedItem($list, $pid, null, 100, 50, 60); // 100 -50% -60 = -10 -> max(0)

        $price = $this->service->getProductPrice($pid, null, null);
        $this->assertSame(0.0, $price['final_price']);
    }

    /** @test */
    public function it_rejects_invalid_product()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->getProductPrice(999, null, null);
    }

    /** @test */
    public function it_rejects_negative_quantity()
    {
        $pid = $this->seedProduct(100);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getProductPrice($pid, null, null, -1);
    }

    /** @test */
    public function it_rejects_variant_not_belonging_to_product()
    {
        $p1 = $this->seedProduct(100);
        $p2 = $this->seedProduct(200);
        $v2 = $this->seedVariant($p2, 50);
        $this->expectException(\RuntimeException::class);
        $this->service->getProductPrice($p1, $v2, null);
    }

    /** @test */
    public function it_prefers_variant_specific_item()
    {
        $pid = $this->seedProduct(100);
        $vid = $this->seedVariant($pid, 120);
        $list = $this->seedPriceList(['name' => 'VariantList', 'priority' => 2]);
        $this->seedItem($list, $pid, null, 90);
        $this->seedItem($list, $pid, $vid, 60);

        $price = $this->service->getProductPrice($pid, $vid, null);
        $this->assertEquals(60.0, $price['final_price']);
    }

    /** @test */
    public function it_applies_discounts()
    {
        $pid = $this->seedProduct(200);
        $list = $this->seedPriceList(['name' => 'Discount', 'priority' => 1]);
        $this->seedItem($list, $pid, null, 0, 10, 5);

        $price = $this->service->getProductPrice($pid, null, null);
        $this->assertEquals(175.0, $price['final_price']); // 200 -10% -5
    }

    private function seedProduct(float $price): int
    {
        $this->db->table('db_products')->insert([
            'code' => 'P' . random_int(100, 999),
            'name' => 'P',
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function seedVariant(int $productId, float $price): int
    {
        $this->db->table('db_product_variants_v2')->insert([
            'product_id' => $productId,
            'sku' => 'V' . random_int(100, 999),
            'price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function seedPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'List ' . random_int(1, 9),
            'type' => 'custom',
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => null,
            'priority' => 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        $payload['apply_to_groups'] = isset($payload['apply_to_groups']) ? json_encode((array) $payload['apply_to_groups']) : null;
        $this->db->table('db_price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price, float $discountPercent = 0, float $discountAmount = 0): void
    {
        $this->db->table('db_price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
