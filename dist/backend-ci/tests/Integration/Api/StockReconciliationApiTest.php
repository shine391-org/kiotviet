<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Stock reconciliation API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class StockReconciliationApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedProductAndBin();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_submits_and_approves_reconciliation()
    {
        $create = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'branch_id' => 1,
                'items' => [
                    ['product_id' => $this->productId, 'counted_qty' => 8, 'unit_cost' => 10],
                ],
            ]))
            ->post('/api/stock-reconciliations');
        $create->assertStatus(201);
        $recon = $this->decode($create)['data'];
        $this->assertEquals('draft', $recon['status']);

        $this->withHeaders($this->jsonHeaders())
            ->post('/api/stock-reconciliations/' . $recon['id'] . '/submit')
            ->assertStatus(200);

        $approve = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['approved_by' => 2]))
            ->post('/api/stock-reconciliations/' . $recon['id'] . '/approve');
        $approve->assertStatus(200);
        $approved = $this->decode($approve)['data'];
        $this->assertEquals('approved', $approved['status']);

        $bin = $this->db->table('stock_bins')
            ->where('product_id', $this->productId)
            ->where('branch_id', 1)
            ->get()->getRowArray();
        $this->assertEquals(8.0, (float) $bin['on_hand_qty']);
    }

    private function seedProductAndBin(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'RECON-1',
            'name' => 'Recon Item',
            'product_type' => 'standard',
            'status' => 'active',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->productId = (int) $this->db->insertID();

        $this->db->table('stock_bins')->insert([
            'product_id' => $this->productId,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
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
