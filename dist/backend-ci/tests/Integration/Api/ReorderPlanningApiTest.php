<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Reorder planning API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class ReorderPlanningApiTest extends CIUnitTestCase
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
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_and_acknowledges_suggestions_via_api()
    {
        $headers = $this->authHeaders(['Content-Type' => 'application/json']);
        $create = $this->withHeaders($headers)
            ->withBody(json_encode([
                'product_id' => 10,
                'branch_id' => 1,
                'min_level' => 5,
                'max_level' => 10,
                'safety_stock' => 2,
            ]))
            ->post('/api/reorder-levels');
        $createBody = $this->decode($create);
        $this->assertTrue($createBody['success'] ?? false, json_encode($createBody));
        $this->seedBin(10, null, 1, 4, 1);

        $generate = $this->withHeaders($headers)
            ->withBody(json_encode(['branch_id' => 1]))
            ->post('/api/purchase-suggestions/generate');
        $generateBody = $this->decode($generate);

        $this->assertTrue($generateBody['success'] ?? false, json_encode($generateBody));
        $this->assertCount(1, $generateBody['data']);
        $suggestion = $generateBody['data'][0];
        $this->assertEquals('pending', $suggestion['status']);
        $this->assertEquals(7.0, (float) $suggestion['suggested_qty']);

        $ack = $this->withHeaders($headers)
            ->withBody(json_encode(['acknowledged_by' => 9]))
            ->post('/api/purchase-suggestions/' . $suggestion['id'] . '/ack');
        $ackBody = $this->decode($ack);
        $this->assertEquals('acknowledged', $ackBody['data']['status']);
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
