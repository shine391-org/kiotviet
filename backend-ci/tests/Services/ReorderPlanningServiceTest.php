<?php

namespace Tests\Services;

use App\Repositories\Inventory\PurchaseSuggestionRepository;
use App\Repositories\Inventory\ReorderLevelRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Services\Inventory\ReorderPlanningService;
use App\Validators\PurchaseSuggestionValidator;
use App\Validators\ReorderLevelValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/** @agent-test: ReorderPlanningService @agent-pattern: Service test with DevDatabaseTrait */
class ReorderPlanningServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ReorderPlanningService $service;
    private ReorderLevelRepository $levels;
    private PurchaseSuggestionRepository $suggestions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();

        $binRepo = new StockBinRepository(null, $this->db);
        $this->levels = new ReorderLevelRepository(null, $this->db);
        $this->suggestions = new PurchaseSuggestionRepository(null, $this->db);
        $this->service = new ReorderPlanningService(
            $this->levels,
            $this->suggestions,
            $binRepo,
            new ReorderLevelValidator(),
            new PurchaseSuggestionValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_suggestions_when_below_threshold()
    {
        $this->levels->create([
            'product_id' => 5,
            'branch_id' => 1,
            'min_level' => 10,
            'max_level' => 20,
            'safety_stock' => 2,
        ]);
        $this->seedBin(5, null, 1, 8, 1);

        $date = date('Y-m-d');
        $result = $this->service->generateSuggestions(['branch_id' => 1, 'generated_for_date' => $date]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $suggestion = $result['data'][0];
        $this->assertEquals('pending', $suggestion['status']);
        $this->assertEquals(13.0, (float) $suggestion['suggested_qty']);
        $this->assertEquals($date, $suggestion['generated_for_date']);
    }

    /** @test */
    public function it_avoids_duplicate_suggestions_on_same_day()
    {
        $this->levels->create([
            'product_id' => 6,
            'branch_id' => 2,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 3,
        ]);
        $this->seedBin(6, null, 2, 4, 0);

        $date = '2025-01-01';
        $first = $this->service->generateSuggestions(['branch_id' => 2, 'generated_for_date' => $date]);
        $second = $this->service->generateSuggestions(['branch_id' => 2, 'generated_for_date' => $date]);

        $this->assertCount(1, $first['data']);
        $this->assertCount(1, $second['data']);
        $this->assertEquals($first['data'][0]['id'], $second['data'][0]['id']);
    }

    /** @test */
    public function it_respects_branch_filters()
    {
        $this->levels->create([
            'product_id' => 7,
            'branch_id' => 3,
            'min_level' => 8,
            'max_level' => 15,
            'safety_stock' => 2,
        ]);
        $this->levels->create([
            'product_id' => 8,
            'branch_id' => 4,
            'min_level' => 5,
            'max_level' => 10,
            'safety_stock' => 1,
        ]);
        $this->seedBin(7, null, 3, 6, 1); // available 5 => shortage
        $this->seedBin(8, null, 4, 20, 0); // no shortage

        $result = $this->service->generateSuggestions(['branch_id' => 3]);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(3, (int) $result['data'][0]['branch_id']);
    }

    private function seedBin(int $productId, ?int $variantId, int $branchId, float $onHand, float $reserved): void
    {
        $this->db->table('stock_bins')->insert([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'branch_id' => $branchId,
            'batch_id' => null,
            'on_hand_qty' => $onHand,
            'reserved_qty' => $reserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
