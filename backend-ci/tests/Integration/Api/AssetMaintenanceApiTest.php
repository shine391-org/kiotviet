<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Asset/Maintenance API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class AssetMaintenanceApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedBase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_asset_depreciation_and_maintenance_flow()
    {
        $assetRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['asset_name' => 'Generator', 'cost' => 1200, 'salvage_value' => 0]))
            ->post('/api/assets');
        $assetRes->assertStatus(201);
        $asset = $this->getJsonFromResponse($assetRes)['data'];

        $depRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'asset_id' => $asset['id'],
                'rate' => 12,
                'total_periods' => 12,
                'start_date' => '2025-01-01',
            ]))
            ->post('/api/depreciation-schedules');
        $depRes->assertStatus(201);
        $schedule = $this->getJsonFromResponse($depRes)['data'];

        $postRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['account_id' => 1, 'expense_account_id' => 2]))
            ->post('/api/depreciation-schedules/' . $schedule['id'] . '/post/1');
        $postRes->assertStatus(200);

        $woRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'asset_id' => $asset['id'],
                'description' => 'Change filter',
            ]))
            ->post('/api/maintenance-work-orders');
        $woRes->assertStatus(201);
        $wo = $this->getJsonFromResponse($woRes)['data'];

        $completeRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'parts' => [
                    ['product_id' => 1, 'qty' => 1, 'warehouse_id' => 1, 'branch_id' => 1],
                ],
            ]))
            ->post('/api/maintenance-work-orders/' . $wo['id'] . '/complete');
        $completeRes->assertStatus(200);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('chart_of_accounts')->insert(['id' => 1, 'name' => 'Asset Acc', 'account_type' => 'asset', 'created_at' => $now]);
        $this->db->table('chart_of_accounts')->insert(['id' => 2, 'name' => 'Expense Acc', 'account_type' => 'expense', 'created_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
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
