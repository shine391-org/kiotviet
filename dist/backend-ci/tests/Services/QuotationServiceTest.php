<?php

namespace Tests\Services;

use App\Services\CRM\QuotationService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: QuotationService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class QuotationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private QuotationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->seedProducts();
        $this->service = new QuotationService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_quote_with_totals()
    {
        $quote = $this->service->create([
            'customer_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);
        $this->assertTrue($quote['success']);
        $data = $quote['data'];
        $this->assertEquals(160.0, (float) $data['total']);
        $this->assertStringStartsWith('Q-', $data['quote_number']);
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
