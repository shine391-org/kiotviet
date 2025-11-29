<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ManufacturingSchemaTrait;

/**
 * @agent-test: Work orders API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class WorkOrdersApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use ManufacturingSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetManufacturingSchema();
        $this->seedBranch();
        $this->seedProducts();
        $this->seedStock();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_completes_work_order_via_api(): void
    {
        $headers = $this->authHeaders(['Content-Type' => 'application/json']);

        $bomResp = $this->withHeaders($headers)->withBody(json_encode([
            'product_id' => 100,
            'version' => 'v1',
            'items' => [
                ['component_product_id' => 1, 'quantity' => 2],
                ['component_product_id' => 2, 'quantity' => 1],
            ],
        ]))->post('/api/boms');
        $bomBody = $this->decode($bomResp);
        $this->assertTrue($bomBody['success'] ?? false, json_encode($bomBody));
        $bomId = $bomBody['data']['id'];

        $createWo = $this->withHeaders($headers)->withBody(json_encode([
            'product_id' => 100,
            'bom_id' => $bomId,
            'quantity' => 1,
            'branch_id' => 1,
        ]))->post('/api/work-orders');
        $woBody = $this->decode($createWo);
        $this->assertTrue($woBody['success'] ?? false, json_encode($woBody));
        $woId = $woBody['data']['id'];

        $startResp = $this->withHeaders($headers)->post('/api/work-orders/' . $woId . '/start');
        $startBody = $this->decode($startResp);
        $this->assertTrue($startBody['success'] ?? false, json_encode($startBody));

        $completeResp = $this->withHeaders($headers)->post('/api/work-orders/' . $woId . '/complete');
        $completeBody = $this->decode($completeResp);
        $this->assertSame('completed', $completeBody['data']['status'] ?? null);

        $binFG = $this->db->table('stock_bins')->where(['product_id' => 100, 'branch_id' => 1])->get()->getRowArray();
        $this->assertEquals(1.0, (float) $binFG['on_hand_qty']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insertBatch([
            ['id' => 100, 'code' => 'FG', 'name' => 'Finished', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 1, 'code' => 'C1', 'name' => 'Comp1', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'C2', 'name' => 'Comp2', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedStock(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('stock_bins')->insertBatch([
            ['product_id' => 1, 'variant_id' => null, 'branch_id' => 1, 'batch_id' => null, 'on_hand_qty' => 10, 'reserved_qty' => 0, 'updated_at' => $now],
            ['product_id' => 2, 'variant_id' => null, 'branch_id' => 1, 'batch_id' => null, 'on_hand_qty' => 5, 'reserved_qty' => 0, 'updated_at' => $now],
        ]);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
