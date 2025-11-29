<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Purchase flow API (PO -> GRN -> LCV)
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class PurchaseFlowApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $poId;
    private array $grn;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_purchase_flow()
    {
        $poRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'branch_id' => 1,
                'items' => [['product_id' => 1, 'quantity' => 2, 'rate' => 50]],
            ]))
            ->post('/api/purchase-orders');
        $poRes->assertStatus(201);
        $po = $this->getJsonFromResponse($poRes)['data'];

        $this->withHeaders($this->jsonHeaders())
            ->post('/api/purchase-orders/' . $po['id'] . '/submit')
            ->assertStatus(200);

        $grnRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'purchase_order_id' => $po['id'],
                'branch_id' => 1,
                'items' => [['product_id' => 1, 'quantity' => 2, 'rate' => 50]],
            ]))
            ->post('/api/goods-receipts');
        $grnRes->assertStatus(201);
        $grn = $this->getJsonFromResponse($grnRes)['data'];

        $lcvRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'goods_receipt_id' => $grn['id'],
                'items' => [
                    ['goods_receipt_item_id' => $grn['items'][0]['id'], 'cost_component' => 'Freight', 'amount' => 10],
                ],
            ]))
            ->post('/api/landed-costs');
        $lcvRes->assertStatus(201);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
