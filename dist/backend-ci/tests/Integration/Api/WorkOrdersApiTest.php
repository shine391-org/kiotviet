<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Work orders API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class WorkOrdersApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $fgProductId;
    private int $componentProductId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedProducts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_bom_and_work_order_flow()
    {
        $bomResponse = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'product_id' => $this->fgProductId,
                'version' => 'v1',
                'quantity' => 1,
                'items' => [
                    ['component_product_id' => $this->componentProductId, 'quantity' => 2],
                ],
            ]))
            ->post('/api/boms');
        $bomResponse->assertStatus(201);
        $bom = $this->decode($bomResponse)['data'];

        $woResponse = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'product_id' => $this->fgProductId,
                'bom_id' => $bom['id'],
                'branch_id' => 1,
                'quantity' => 3,
            ]))
            ->post('/api/work-orders');
        $woResponse->assertStatus(201);
        $wo = $this->decode($woResponse)['data'];

        $this->withHeaders($this->jsonHeaders())->post("/api/work-orders/{$wo['id']}/release")->assertStatus(200);
        $this->withHeaders($this->jsonHeaders())->post("/api/work-orders/{$wo['id']}/start")->assertStatus(200);
        $completed = $this->withHeaders($this->jsonHeaders())->post("/api/work-orders/{$wo['id']}/complete");
        $completed->assertStatus(200);
        $payload = $this->decode($completed);
        $this->assertEquals('completed', $payload['data']['status']);

        $componentBin = $this->db->table('stock_bins')
            ->where('product_id', $this->componentProductId)
            ->where('branch_id', 1)
            ->get()->getRowArray();
        $this->assertEquals(4.0, (float) $componentBin['on_hand_qty']);

        $fgBin = $this->db->table('stock_bins')
            ->where('product_id', $this->fgProductId)
            ->where('branch_id', 1)
            ->get()->getRowArray();
        $this->assertEquals(3.0, (float) $fgBin['on_hand_qty']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'FG-1',
            'name' => 'Finished Good',
            'product_type' => 'standard',
            'is_active' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->fgProductId = (int) $this->db->insertID();

        $this->db->table('products')->insert([
            'code' => 'COMP-1',
            'name' => 'Component',
            'product_type' => 'standard',
            'is_active' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->componentProductId = (int) $this->db->insertID();

        $this->db->table('stock_bins')->insert([
            'product_id' => $this->componentProductId,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        return json_decode($raw, true);
    }
}
