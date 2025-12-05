<?php

namespace Tests\Services;

use App\Services\CRM\OpportunityService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: OpportunityService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class OpportunityServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private OpportunityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->seedProducts();
        $this->service = new OpportunityService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_opportunity_and_updates_stage()
    {
        $opp = $this->service->create([
            'title' => 'Deal 1',
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);
        $this->assertTrue($opp['success']);
        $data = $opp['data'];
        $this->assertEquals('qualification', $data['stage']);

        $updated = $this->service->updateStage($data['id'], 'proposal', 60);
        $this->assertEquals('proposal', $updated['data']['stage']);
        $this->assertEquals(60, (int) $updated['data']['probability']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Prod',
            'selling_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'Base',
            'type' => 'custom',
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'price' => 80,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
