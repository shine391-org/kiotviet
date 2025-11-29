<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: Stock Reconciliation API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class StockReconciliationsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_approves_reconciliation_via_api()
    {
        $create = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'branch_id' => 1,
                'items' => [
                    ['product_id' => 1, 'counted_qty' => 8],
                ],
            ]))
            ->post('/api/stock-reconciliations');
        $createBody = $this->decode($create);
        $this->assertTrue($createBody['success'] ?? false, json_encode($createBody));
        $id = $createBody['data']['id'];

        $submit = $this->withHeaders($this->authHeaders())->post('/api/stock-reconciliations/' . $id . '/submit');
        $this->assertTrue($this->decode($submit)['success'] ?? false);

        $approve = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['approved_by' => 9]))
            ->post('/api/stock-reconciliations/' . $id . '/approve');
        $approveBody = $this->decode($approve);
        $this->assertEquals('approved', $approveBody['data']['status']);

        $bin = $this->db->table('stock_bins')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(8.0, (float) $bin['on_hand_qty']);
    }

    /** @test */
    public function it_rejects_without_affecting_bin()
    {
        $create = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'branch_id' => 1,
                'items' => [
                    ['product_id' => 1, 'counted_qty' => 2],
                ],
            ]))
            ->post('/api/stock-reconciliations');
        $id = $this->decode($create)['data']['id'];

        $reject = $this->withHeaders($this->authHeaders())->post('/api/stock-reconciliations/' . $id . '/reject');
        $this->assertTrue($this->decode($reject)['success'] ?? false);

        $bin = $this->db->table('stock_bins')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $bin['on_hand_qty']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
