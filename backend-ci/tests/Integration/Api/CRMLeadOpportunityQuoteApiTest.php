<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CRM lead -> opportunity -> quotation API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class CRMLeadOpportunityQuoteApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->seedBase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_lead_to_opportunity_to_quotation_flow()
    {
        $leadRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['name' => 'Lead A', 'email' => 'a@test.com']))
            ->post('/api/leads');
        $leadRes->assertStatus(201);
        $lead = $this->getJsonFromResponse($leadRes)['data'];

        $oppRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'lead_id' => $lead['id'],
                'title' => 'Deal',
                'items' => [['product_id' => $this->productId, 'quantity' => 1]],
            ]))
            ->post('/api/opportunities');
        $oppRes->assertStatus(201);
        $opp = $this->getJsonFromResponse($oppRes)['data'];

        $quoteRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'opportunity_id' => $opp['id'],
                'customer_id' => null,
                'items' => [['product_id' => $this->productId, 'quantity' => 2]],
            ]))
            ->post('/api/quotations');
        $quoteRes->assertStatus(201);
        $quote = $this->getJsonFromResponse($quoteRes)['data'];
        $this->assertEquals(160.0, (float) $quote['total']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('payment_methods')->insert([
            'code' => 'CASH',
            'name' => 'Cash',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('products')->insert([
            'code' => 'P1',
            'name' => 'Prod',
            'selling_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->productId = (int) $this->db->insertID();
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
            'product_id' => $this->productId,
            'price' => 80,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
