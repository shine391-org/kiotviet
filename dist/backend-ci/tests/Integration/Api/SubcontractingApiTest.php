<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Subcontracting API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class SubcontractingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $fgProductId;
    private int $materialProductId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->db->table('products')->truncate();
        $this->db->table('stock_bins')->truncate();
        $this->db->table('subcontracting_orders')->truncate();
        $this->setUpAuthToken();
        $this->seedProducts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_issues_and_receives_subcontracting_order()
    {
        $create = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'supplier_id' => 101,
                'product_id' => $this->fgProductId,
                'quantity' => 5,
                'materials' => [
                    ['material_product_id' => $this->materialProductId, 'quantity' => 3],
                ],
            ]))
            ->post('/api/subcontracting');
        $create->assertStatus(201);
        $order = $this->decode($create)['data'];
        $this->assertEquals('draft', $order['status']);

        $issue = $this->withHeaders($this->jsonHeaders())
            ->post('/api/subcontracting/' . $order['id'] . '/issue');
        $issue->assertStatus(200);
        $issued = $this->decode($issue)['data'];
        $this->assertEquals('materials_issued', $issued['status']);

        $receive = $this->withHeaders($this->jsonHeaders())
            ->post('/api/subcontracting/' . $order['id'] . '/receive');
        $receive->assertStatus(200);
        $received = $this->decode($receive)['data'];
        $this->assertEquals('completed', $received['status']);

        $fgBin = $this->db->table('stock_bins')
            ->where('product_id', $this->fgProductId)
            ->where('branch_id', 1)
            ->get()->getRowArray();
        $this->assertEquals(5.0, (float) $fgBin['on_hand_qty']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'SUB-FG',
            'name' => 'Sub FG',
            'product_type' => 'standard',
            'status' => 'active',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->fgProductId = (int) $this->db->insertID();

        $this->db->table('products')->insert([
            'code' => 'SUB-MAT',
            'name' => 'Sub Material',
            'product_type' => 'raw',
            'status' => 'active',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->materialProductId = (int) $this->db->insertID();
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
